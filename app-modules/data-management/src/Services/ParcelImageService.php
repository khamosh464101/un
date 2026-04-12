<?php

namespace Modules\DataManagement\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\DataManagement\Models\Parcel;
use Modules\DataManagement\Models\ParcelImage;
use Modules\DataManagement\Models\ParcelStyle;

class ParcelImageService
{
    protected $mapWidth = 800;
    protected $mapHeight = 570;
    protected $zoom = 20;
    protected $tileSize = 256;
    protected $centerLat;
    protected $centerLng;
    protected $fontSizes = [1, 2, 3, 4, 5];
    
    /**
     * Initialize generation for all parcels
     */
    public function initializeForAllParcels(): int
    {
        // Get all parcel IDs that don't have pending/completed images
        $parcelIds = Parcel::leftJoin('dm_parcel_images', 'dm_parcels.id', '=', 'dm_parcel_images.parcel_id')
            ->whereNull('dm_parcel_images.id')
            ->pluck('dm_parcels.id')
            ->toArray();
        
        // Create pending records for each parcel
        foreach ($parcelIds as $parcelId) {
            ParcelImage::create([
                'parcel_id' => $parcelId,
                'image_path' => '',
                'filename' => '',
                'status' => 'pending'
            ]);
        }
        
        Log::info("Initialized " . count($parcelIds) . " parcels for image generation");
        return count($parcelIds);
    }
    
    /**
     * Process a batch of parcels
     */
    public function processBatch(int $batchSize = 10): array
    {
        // Get pending parcels
        $pendingImages = ParcelImage::where('status', 'pending')
            ->limit($batchSize)
            ->get();
        
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($pendingImages as $pendingImage) {
            $results['processed']++;
            
            try {
                // Update status to processing
                $pendingImage->update(['status' => 'processing']);
                
                // Generate the image
                $result = $this->generateForParcel($pendingImage->parcel);
                
                if ($result['success']) {
                    $pendingImage->update([
                        'status' => 'completed',
                        'image_path' => $result['image_path'],
                        'filename' => basename($result['image_path']),
                        'file_size' => $result['file_size'],
                        'processed_at' => now()
                    ]);
                    $results['successful']++;
                } else {
                    throw new \Exception($result['error']);
                }
                
            } catch (\Exception $e) {
                $pendingImage->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count' => $pendingImage->retry_count + 1
                ]);
                $results['failed']++;
                $results['errors'][] = [
                    'parcel_id' => $pendingImage->parcel_id,
                    'parcel_code' => $pendingImage->parcel->parcel_code ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Generate image for a single parcel
     */
    public function generateForParcel(Parcel $parcel): array
    {
        try {
            // Get all parcels for context
            $allParcels = $this->getAllParcels();
            
            if (empty($allParcels)) {
                throw new \Exception("No parcels found in database");
            }
            
            // Find this parcel in the array
            $selectedParcelData = null;
            foreach ($allParcels as $p) {
                if ($p['id'] == $parcel->id) {
                    $selectedParcelData = $p;
                    break;
                }
            }
            
            if (!$selectedParcelData) {
                throw new \Exception("Parcel data not found");
            }
            
            // Calculate center from selected parcel
            $center = $this->getPolygonCenter($selectedParcelData['geojson']);
            $this->centerLat = $center['lat'];
            $this->centerLng = $center['lng'];
            
            // Build the map image
            $imagePath = $this->buildMapWithParcels($allParcels, $selectedParcelData);
            
            if (!$imagePath) {
                throw new \Exception("Failed to generate image");
            }
            
            // Get file size
            $fullPath = Storage::disk('public')->path($imagePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
            
            return [
                'success' => true,
                'image_path' => $imagePath,
                'file_size' => $fileSize
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to generate image for parcel {$parcel->parcel_code}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all parcels from database
     */
    private function getAllParcels(): array
    {
        $parcels = Parcel::all();
        
        $data = [];
        foreach ($parcels as $parcel) {
            if ($parcel->geometry) {
                $data[] = [
                    'id' => $parcel->id,
                    'code' => $parcel->parcel_code,
                    'geojson' => is_string($parcel->geometry) 
                        ? json_decode($parcel->geometry, true) 
                        : $parcel->geometry,
                    'land_use_type' => $parcel->land_use_type ?? 'Unknown',
                    'attributes'    => is_string($parcel->attributes)
                        ? json_decode($parcel->attributes, true)
                        : ($parcel->attributes ?? []),
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Build map with parcels (YOUR EXISTING CODE)
     */
    private function buildMapWithParcels(array $allParcels, array $selectedParcel)
    {
        // Calculate world coordinates for center
        $centerWorldX = $this->latLngToWorldX($this->centerLng, $this->zoom);
        $centerWorldY = $this->latLngToWorldY($this->centerLat, $this->zoom);
        
        // Calculate which tiles we need
        $minWorldX = $centerWorldX - ($this->mapWidth / 2);
        $maxWorldX = $centerWorldX + ($this->mapWidth / 2);
        $minWorldY = $centerWorldY - ($this->mapHeight / 2);
        $maxWorldY = $centerWorldY + ($this->mapHeight / 2);
        
        $minTileX = floor($minWorldX / $this->tileSize);
        $maxTileX = floor($maxWorldX / $this->tileSize);
        $minTileY = floor($minWorldY / $this->tileSize);
        $maxTileY = floor($maxWorldY / $this->tileSize);
        
        $tilesPerRow = $maxTileX - $minTileX + 1;
        $tilesPerCol = $maxTileY - $minTileY + 1;
        
        // Create base image
        $mapImage = imagecreatetruecolor($this->mapWidth, $this->mapHeight);
        $bgColor = imagecolorallocate($mapImage, 240, 240, 240);
        imagefill($mapImage, 0, 0, $bgColor);
        
        // Download and place tiles
        $this->downloadTilesWorld(
            $mapImage, 
            $minTileX, 
            $minTileY, 
            $tilesPerRow, 
            $tilesPerCol,
            $centerWorldX,
            $centerWorldY
        );

        $parcelStyle = ParcelStyle::first();
        
        // Draw all parcels (non-selected first)
        foreach ($allParcels as $parcel) {
            $isSelected = ($parcel['id'] == $selectedParcel['id']);
            if (!$isSelected) {
                $this->drawPolygon($mapImage, $parcel['geojson'], false);
                $this->drawLabelInsidePolygon($mapImage, $parcel['geojson'], $parcel['code'], $parcelStyle->other_color);
            }
        }
        
        // Draw selected parcel on top
        $this->drawPolygon($mapImage, $selectedParcel['geojson'], true);
        $this->drawLabelInsidePolygon($mapImage, $selectedParcel['geojson'], $selectedParcel['code'], $parcelStyle->selected_color, true);

        
        // Add header
        $this->drawHeader($mapImage, $selectedParcel);

        // ── Dark 1px border around the entire image (map + header) ───────────
        $borderColor = imagecolorallocate($mapImage, 40, 40, 40);
        imagerectangle($mapImage, 0, 0, $this->mapWidth - 1, $this->mapHeight - 1, $borderColor);
        // Separator line between header and map body
        $headerHeight = 54; // must match drawHeader value
        imageline($mapImage, 0, $headerHeight, $this->mapWidth - 1, $headerHeight, $borderColor);
        
        // ///////// Save image in local storage ////////
        // $filename = 'parcel-images/' . $selectedParcel['code'] . '.jpg';
        // $path = Storage::disk('public')->path($filename);
        
        // if (!is_dir(dirname($path))) {
        //     mkdir(dirname($path), 0755, true);
        // }
        
        // imagejpeg($mapImage, $path, 90);
        // imagedestroy($mapImage);
        
        // return $filename;

        // ///////// Save in google storage //////////

        $filename = 'jpg/' . $selectedParcel['code'] . '.jpg';

        // Capture image output into memory
        ob_start();
        imagejpeg($mapImage, null, 100);
        $imageContent = ob_get_clean();

        // Upload to Google Cloud Storage
        Storage::disk('gcs')->put($filename, $imageContent, 'public');

        imagedestroy($mapImage);

        return $filename;
    }
    
    /**
     * Download tiles (YOUR EXISTING CODE)
     */
    private function downloadTilesWorld($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $centerWorldX, $centerWorldY)
    {
        $tileDownloaded = false;
        $tileServer = 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';
        
        for ($row = 0; $row < $tilesPerCol; $row++) {
            for ($col = 0; $col < $tilesPerRow; $col++) {
                $tileX = $startX + $col;
                $tileY = $startY + $row;
                
                $tileUrl = str_replace(
                    ['{x}', '{y}', '{z}'],
                    [$tileX, $tileY, $this->zoom],
                    $tileServer
                );
                
                try {
                    $response = Http::timeout(5)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Referer' => 'https://www.unhabitat.org/'
                        ])
                        ->get($tileUrl);
                    
                    if ($response->successful()) {
                        $tileImage = @imagecreatefromstring($response->body());
                        
                        if ($tileImage) {
                            $tileWorldX = $tileX * $this->tileSize;
                            $tileWorldY = $tileY * $this->tileSize;
                            
                            $destX = ($tileWorldX - $centerWorldX) + ($this->mapWidth / 2);
                            $destY = ($tileWorldY - $centerWorldY) + ($this->mapHeight / 2);
                            
                            imagecopy($mapImage, $tileImage, 
                                (int)$destX, (int)$destY, 
                                0, 0, 
                                $this->tileSize, $this->tileSize);
                            
                            imagedestroy($tileImage);
                            $tileDownloaded = true;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to download tile: {$tileUrl}");
                }
            }
        }
        
        if (!$tileDownloaded) {
            $this->downloadOSMTilesWorld($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $centerWorldX, $centerWorldY);
        }
    }
    
    /**
     * Download OSM tiles (YOUR EXISTING CODE)
     */
    private function downloadOSMTilesWorld($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $centerWorldX, $centerWorldY)
    {
        for ($row = 0; $row < $tilesPerCol; $row++) {
            for ($col = 0; $col < $tilesPerRow; $col++) {
                $tileX = $startX + $col;
                $tileY = $startY + $row;
                
                $tileUrl = "https://tile.openstreetmap.org/{$this->zoom}/{$tileX}/{$tileY}.png";
                
                try {
                    $response = Http::timeout(5)->get($tileUrl);
                    
                    if ($response->successful()) {
                        $tileImage = @imagecreatefromstring($response->body());
                        
                        if ($tileImage) {
                            $tileWorldX = $tileX * $this->tileSize;
                            $tileWorldY = $tileY * $this->tileSize;
                            
                            $destX = ($tileWorldX - $centerWorldX) + ($this->mapWidth / 2);
                            $destY = ($tileWorldY - $centerWorldY) + ($this->mapHeight / 2);
                            
                            imagecopy($mapImage, $tileImage, 
                                (int)$destX, (int)$destY, 
                                0, 0, 
                                $this->tileSize, $this->tileSize);
                            
                            imagedestroy($tileImage);
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
    
    /**
     * Draw polygon (YOUR EXISTING CODE)
     */
    private function drawPolygon($image, $geojson, bool $isSelected)
    {
        $coordinates = match ($geojson['type'] ?? null) {
            'Polygon' => $geojson['coordinates'][0],
            'MultiPolygon' => $geojson['coordinates'][0][0],
            default => []
        };
        
        $points = [];
        foreach ($coordinates as $coord) {
            $pixel = $this->latLngToPixel($coord[1], $coord[0], $this->zoom);
            
            if ($pixel['x'] >= -100 && $pixel['x'] <= $this->mapWidth + 100 &&
                $pixel['y'] >= -100 && $pixel['y'] <= $this->mapHeight + 100) {
                $points[] = $pixel;
            }
        }
        
        if (count($points) < 3) return;
        
        $gdPoints = [];
        foreach ($points as $point) {
            $gdPoints[] = (int)$point['x'];
            $gdPoints[] = (int)$point['y'];
        }

        $parcelStyle = ParcelStyle::first();
        
        if ($isSelected) {
            // Selected parcel: Thick yellow border
            

            list($r, $g, $b) = sscanf($parcelStyle->selected_color, "#%02x%02x%02x");
            $borderColor = imagecolorallocatealpha($image, $r, $g, $b, 0);
            $borderWeight = $parcelStyle->selected_weight ?? 4;
            
            for ($i = 0; $i < $borderWeight; $i++) {
                $offsetPoints = [];
                foreach ($points as $point) {
                    $offsetPoints[] = (int)($point['x'] + $i);
                    $offsetPoints[] = (int)($point['y'] + $i);
                }
                imagepolygon($image, $offsetPoints, count($points), $borderColor);
            }
        } else {
            // Other parcels: White fill with gray border
            $fillColor = imagecolorallocatealpha($image, 255, 255, 255, $parcelStyle->other_opacity * 100 ?? 80);
            list($r, $g, $b) = sscanf($parcelStyle->other_color, "#%02x%02x%02x");
            $borderColor = imagecolorallocatealpha($image, $r, $g, $b, 0);
            $borderWeight = $parcelStyle->other_weight ?? 2;
            
            imagefilledpolygon($image, $gdPoints, count($points), $fillColor);
            
            for ($i = 0; $i < $borderWeight; $i++) {
                $offsetPoints = [];
                foreach ($points as $point) {
                    $offsetPoints[] = (int)($point['x'] + $i);
                    $offsetPoints[] = (int)($point['y'] + $i);
                }
                imagepolygon($image, $offsetPoints, count($points), $borderColor);
            }
        }
    }
    
    /**
 * Draw label inside polygon (YOUR EXISTING CODE)
 */
private function drawLabelInsidePolygon($image, $geojson, string $text, string $color, bool $selected = false)
{
    $coordinates = match ($geojson['type'] ?? null) {
        'Polygon' => $geojson['coordinates'][0],
        'MultiPolygon' => $geojson['coordinates'][0][0],
        default => []
    };
    
    if (empty($coordinates)) {
        return;
    }
    
    $bounds = $this->getPolygonBounds($coordinates);
    
    if (!$bounds) {
        return;
    }
    
    $centerX = ($bounds['minX'] + $bounds['maxX']) / 2;
    $centerY = ($bounds['minY'] + $bounds['maxY']) / 2;
    
    if ($centerX < 0 || $centerX > $this->mapWidth || $centerY < 0 || $centerY > $this->mapHeight) {
        return;
    }
    

   if ($selected) {
        // Selected parcel - use large point size
        $fontSize = 24; // Point size, not GD font index
    } else {
        $availableWidth = $bounds['maxX'] - $bounds['minX'];
        $availableHeight = $bounds['maxY'] - $bounds['minY'];
        // Calculate appropriate point size based on available space
        $fontSize = min(16, max(8, $availableWidth / 15)); // Example calculation
    }
    logger()->info("Calculated font size {$fontSize} for label '{$text}'");
    
    $this->drawTextLabel($image, $text, (int)$centerX, (int)$centerY, $fontSize, $color, $selected);
}
    
    /**
     * Get polygon bounds (YOUR EXISTING CODE)
     */
    private function getPolygonBounds($coordinates)
    {
        $minX = INF;
        $minY = INF;
        $maxX = -INF;
        $maxY = -INF;
        $validPoints = 0;
        
        foreach ($coordinates as $coord) {
            $pixel = $this->latLngToPixel($coord[1], $coord[0], $this->zoom);
            
            if ($pixel['x'] >= -500 && $pixel['x'] <= $this->mapWidth + 500 &&
                $pixel['y'] >= -500 && $pixel['y'] <= $this->mapHeight + 500) {
                
                $minX = min($minX, $pixel['x']);
                $minY = min($minY, $pixel['y']);
                $maxX = max($maxX, $pixel['x']);
                $maxY = max($maxY, $pixel['y']);
                $validPoints++;
            }
        }
        
        if ($validPoints < 3) {
            return null;
        }
        
        return [
            'minX' => $minX,
            'minY' => $minY,
            'maxX' => $maxX,
            'maxY' => $maxY
        ];
    }
    
    private function findBestFontSize(string $text, float $availableWidth, float $availableHeight): int
{
    $textLength = strlen($text);
    
    // Start from largest and go down, but limit to 4 max for non-selected
    // This ensures selected (size 5) is always bigger
    foreach (array_reverse($this->fontSizes) as $size) {
        // Skip size 5 for non-selected parcels
        if ($size >= 5) continue;
        
        $charWidth = imagefontwidth($size);
        $charHeight = imagefontheight($size);
        
        $textWidth = $textLength * $charWidth;
        $textHeight = $charHeight;
        
        if ($textWidth < $availableWidth * 0.8 && $textHeight < $availableHeight * 0.8) {
            return $size;
        }
    }
    
    return 1; // Smallest as fallback
}
    

 private function drawTextLabel($image, string $text, int $x, int $y, int $fontSize, $color, $selected)
{
     // Detect environment
    $isLocal = app()->environment('local');
    
    if ($isLocal) {
        // Use Mac font paths
        $fontFile = $this->getMacFontPath();
    } else {
        // Use Linux server font paths
        $fontFile = $this->getLinuxFontPath();
    }

    
    if (file_exists($fontFile) && is_readable($fontFile)) {
        try {
            $actualSize = $selected ? 16 : 12;
            
            // Get text dimensions first (needed for both outline and main text)
            $bbox = imagettfbbox($actualSize, 0, $fontFile, $text);
            if ($bbox === false) {
                throw new \Exception("Failed to get text bounding box");
            }
            
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            
            $textX = $x - ($textWidth / 2);
            $textY = $y + ($textHeight / 2);
            
            if ($selected) {
                // SELECTED: Outline = $color, Main text = Black
                
                // Parse outline color from parameter
                list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
                $outlineColor = imagecolorallocate($image, $r, $g, $b);
                
                // Main text color (Black)
                $mainColor = imagecolorallocate($image, 0, 0, 0);
                
                // Draw outline (thicker for selected)
                for ($dx = -2; $dx <= 2; $dx++) {
                    for ($dy = -2; $dy <= 2; $dy++) {
                        if ($dx != 0 || $dy != 0) {
                            imagettftext($image, $actualSize, 0, (int)($textX + $dx), (int)($textY + $dy), $outlineColor, $fontFile, $text);
                        }
                    }
                }
                
                // Draw main text
                imagettftext($image, $actualSize, 0, (int)$textX, (int)$textY, $mainColor, $fontFile, $text);
                
            } else {
                // NOT SELECTED: Outline = White, Main text = Black
                
                // Outline color (White)
                $outlineColor = imagecolorallocate($image, 255, 255, 255);
                
                // Main text color (Black)
                list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
                $mainColor = imagecolorallocate($image, $r, $g, $b);
                
                // Draw outline (thinner for non-selected)
                for ($dx = -1; $dx <= 1; $dx++) {
                    for ($dy = -1; $dy <= 1; $dy++) {
                        if ($dx != 0 || $dy != 0) {
                            imagettftext($image, $actualSize, 0, (int)($textX + $dx), (int)($textY + $dy), $outlineColor, $fontFile, $text);
                        }
                    }
                }
                
                // Draw main text
                imagettftext($image, $actualSize, 0, (int)$textX, (int)$textY, $mainColor, $fontFile, $text);
            }
            
            return;
            
        } catch (\Exception $e) {
            Log::warning("TTF failed with system font: " . $e->getMessage());
        }
    }
    
    // Fallback to GD
    $this->drawTextLabelFallback($image, $text, $x, $y, $fontSize, $color, $selected);
}

private function getMacFontPath(): ?string
{
    $paths = [
        '/Library/Fonts/Arial.ttf',
        '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/Applications/XAMPP/xamppfiles/lib/fonts/FreeSans.ttf',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

private function getMacBoldFontPath(): ?string
{
    $paths = [
        '/Library/Fonts/Arial Bold.ttf',
        '/Library/Fonts/Arial-BoldMT.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/System/Library/Fonts/Supplemental/Arial-BoldMT.ttf',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    // fallback to regular
    return $this->getMacFontPath();
}

private function getLinuxFontPath(): ?string
{
    $paths = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

private function getLinuxBoldFontPath(): ?string
{
    $paths = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    // fallback to regular
    return $this->getLinuxFontPath();
}
    
    /**
     * Draw header matching the reference design:
     * [AHF logo white box] | [Blue band: white bold text centered + N arrow top-right] | [UN-HABITAT logo white box]
     *
     * Reference image dimensions ~1050×720, header ~70px tall.
     * Logo boxes: ~100px left, ~80px right.
     * Blue band fills the rest. Text is white, bold, ~15pt. North arrow is black.
     */
    private function drawHeader($image, $parcel)
    {
        $width        = imagesx($image);
        $headerHeight = 54;   // smaller overall header
        $padding      = 4;    // tighter padding so logos get more room

        // ── 1. Full white background ─────────────────────────────────────────
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $headerHeight, $white);

        // ── 2. Logo box widths — AHF gets more space ─────────────────────────
        $leftLogoW  = (int)($width * 0.145) + 13; // ~129px — extra 13px for AHF
        $rightLogoW = (int)($width * 0.095); // ~76px

        // ── 3. Blue center band with 1px vertical margin ─────────────────────
        $bandX1    = $leftLogoW;
        $bandX2    = $width - $rightLogoW;
        $bandMargin = 1; // 1px top and bottom margin
        $blue      = imagecolorallocate($image, 93, 173, 226); // #5DADE2
        imagefilledrectangle($image, $bandX1, $bandMargin, $bandX2, $headerHeight - $bandMargin, $blue);

        // ── 4. Bold font ──────────────────────────────────────────────────────
        $isLocal   = app()->environment('local');
        $boldFont  = $isLocal ? $this->getMacBoldFontPath() : $this->getLinuxBoldFontPath();
        $whiteText = imagecolorallocate($image, 255, 255, 255);

        $attrs    = $parcel['attributes'] ?? [];
        $district   = $attrs['DISTRICT']   ?? 'Unknown';
        $provinceC  = $attrs['PROVINCE_C'] ?? '??';
        $line1 = "AFGHANISTAN: {$district} - PD#{$provinceC}";
        $line2 = "Parcel No: " . $parcel['code'];

        // North arrow sits inside the band, right side — reserve space for it
        $arrowReserve = 42;
        $bandTextX2   = $bandX2 - $arrowReserve;
        $bandCenter   = $bandX1 + (($bandTextX2 - $bandX1) / 2);

        if ($boldFont && file_exists($boldFont)) {
            $fontSize = 12; // scaled down to fit the smaller header

            // Line 1 — vertically at ~38% of header height
            $bbox1 = imagettfbbox($fontSize, 0, $boldFont, $line1);
            $tw1   = abs($bbox1[2] - $bbox1[0]);
            $th1   = abs($bbox1[1] - $bbox1[7]);
            $tx1   = (int)($bandCenter - $tw1 / 2);
            $ty1   = (int)(($headerHeight / 2) - 2);          // baseline of line 1
            imagettftext($image, $fontSize, 0, $tx1, $ty1, $whiteText, $boldFont, $line1);

            // Line 2 — vertically at ~75% of header height
            $bbox2 = imagettfbbox($fontSize, 0, $boldFont, $line2);
            $tw2   = abs($bbox2[2] - $bbox2[0]);
            $tx2   = (int)($bandCenter - $tw2 / 2);
            $ty2   = (int)($ty1 + $th1 + 5);                  // 5px gap between lines
            imagettftext($image, $fontSize, 0, $tx2, $ty2, $whiteText, $boldFont, $line2);

        } else {
            // GD built-in fallback (no bold, but best we can do)
            $font = 4; // largest built-in GD font
            $tw1  = strlen($line1) * imagefontwidth($font);
            imagestring($image, $font, (int)($bandCenter - $tw1 / 2), 10, $line1, $whiteText);
            $tw2 = strlen($line2) * imagefontwidth($font);
            imagestring($image, $font, (int)($bandCenter - $tw2 / 2), 38, $line2, $whiteText);
        }

        // ── 5. North arrow — inside blue band, right side, vertically centered ─
        $arrowX = $bandX2 - (int)($arrowReserve / 2);
        $arrowY = (int)($headerHeight / 2) + 6; // +6px total (was +3, now +3 more)
        $this->drawNorthArrow($image, $arrowX, $arrowY);

        // ── 6. Left logo (AHF) ────────────────────────────────────────────────
        $ahfPath = public_path('logos/ahf_logo.png');
        if (file_exists($ahfPath)) {
            $logo = @imagecreatefrompng($ahfPath);
            if ($logo) {
                $lw    = imagesx($logo);
                $lh    = imagesy($logo);
                $scale = min(($leftLogoW - $padding * 2) / $lw, ($headerHeight - $padding * 2) / $lh);
                $dstW  = (int)($lw * $scale);
                $dstH  = (int)($lh * $scale);
                $dstX  = (int)(($leftLogoW - $dstW) / 2);
                $dstY  = (int)(($headerHeight - $dstH) / 2);
                imagecopyresampled($image, $logo, $dstX, $dstY, 0, 0, $dstW, $dstH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        // ── 7. Right logo (UN-HABITAT) ────────────────────────────────────────
        $unPath = public_path('logos/habitat.png');
        if (!file_exists($unPath)) {
            $unPath = public_path('logos/un_habitat.png');
        }
        if (file_exists($unPath)) {
            $ext  = strtolower(pathinfo($unPath, PATHINFO_EXTENSION));
            $logo = match($ext) {
                'png'          => @imagecreatefrompng($unPath),
                'jpg', 'jpeg'  => @imagecreatefromjpeg($unPath),
                default        => null,
            };
            if ($logo) {
                $lw    = imagesx($logo);
                $lh    = imagesy($logo);
                $scale = min(($rightLogoW - $padding * 2) / $lw, ($headerHeight - $padding * 2) / $lh);
                $dstW  = (int)($lw * $scale);
                $dstH  = (int)($lh * $scale);
                $dstX  = (int)($width - $rightLogoW + ($rightLogoW - $dstW) / 2);
                $dstY  = (int)(($headerHeight - $dstH) / 2);
                imagecopyresampled($image, $logo, $dstX, $dstY, 0, 0, $dstW, $dstH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        // ── 8. Thin gray separator under header ───────────────────────────────
        $gray = imagecolorallocate($image, 160, 160, 160);
        imageline($image, 0, $headerHeight, $width, $headerHeight, $gray);
    }

    /**
     * North arrow — black, sits on the blue band.
     * "N" label on top, filled arrowhead pointing up, vertical shaft below.
     */
    private function drawNorthArrow($image, $x, $y)
    {
        $black = imagecolorallocate($image, 0, 0, 0);

        // Vertical shaft
        imageline($image, $x, $y - 10, $x, $y + 10, $black);
        imageline($image, $x + 1, $y - 10, $x + 1, $y + 10, $black); // 2px thick

        // Filled arrowhead pointing up
        $arrowPoints = [
            $x,     $y - 18,  // tip
            $x - 6, $y - 6,   // bottom-left
            $x + 6, $y - 6,   // bottom-right
        ];
        imagefilledpolygon($image, $arrowPoints, 3, $black);

        // "N" label above the arrow
        $isLocal  = app()->environment('local');
        $fontFile = $isLocal ? $this->getMacBoldFontPath() : $this->getLinuxBoldFontPath();
        if ($fontFile && file_exists($fontFile)) {
            imagettftext($image, 10, 0, $x - 5, $y - 20, $black, $fontFile, 'N');
        } else {
            imagestring($image, 2, $x - 3, $y - 32, 'N', $black);
        }
    }
    
    private function drawLogosFromFiles($image, $startX, $startY)
{
    // Path to your logo files
    $logo1Path = public_path('images/logo1.png');
    $logo2Path = public_path('images/logo2.png');
    
    if (file_exists($logo1Path)) {
        $logo1 = imagecreatefrompng($logo1Path);
        imagecopy($image, $logo1, $startX, $startY, 0, 0, 30, 30);
        imagedestroy($logo1);
    }
    
    if (file_exists($logo2Path)) {
        $logo2 = imagecreatefrompng($logo2Path);
        imagecopy($image, $logo2, $startX + 40, $startY, 0, 0, 30, 30);
        imagedestroy($logo2);
    }
}
    /**
     * Draw logos (YOUR EXISTING CODE)
     */
    private function drawLogos($image, $startX, $startY)
    {
        $whiteColor = imagecolorallocate($image, 255, 255, 255);
        
        imagerectangle($image, $startX, $startY, $startX + 30, $startY + 30, $whiteColor);
        imagerectangle($image, $startX + 40, $startY, $startX + 70, $startY + 30, $whiteColor);
        
        imagestring($image, 1, $startX + 5, $startY + 10, "LOGO", $whiteColor);
        imagestring($image, 1, $startX + 45, $startY + 10, "LOGO", $whiteColor);
    }
    
    /**
     * Convert lat/lng to pixel (YOUR EXISTING CODE)
     */
    private function latLngToPixel($lat, $lng, $zoom)
    {
        $worldX = $this->latLngToWorldX($lng, $zoom);
        $worldY = $this->latLngToWorldY($lat, $zoom);
        
        $centerWorldX = $this->latLngToWorldX($this->centerLng, $zoom);
        $centerWorldY = $this->latLngToWorldY($this->centerLat, $zoom);
        
        $pixelX = ($worldX - $centerWorldX) + ($this->mapWidth / 2);
        $pixelY = ($worldY - $centerWorldY) + ($this->mapHeight / 2);
        
        return ['x' => $pixelX, 'y' => $pixelY];
    }
    
    /**
     * Convert lng to world X (YOUR EXISTING CODE)
     */
    private function latLngToWorldX($lng, $zoom)
    {
        return (($lng + 180) / 360) * pow(2, $zoom) * $this->tileSize;
    }
    
    /**
     * Convert lat to world Y (YOUR EXISTING CODE)
     */
    private function latLngToWorldY($lat, $zoom)
    {
        $sinLat = sin(deg2rad($lat));
        return (0.5 - log((1 + $sinLat) / (1 - $sinLat)) / (4 * M_PI)) * pow(2, $zoom) * $this->tileSize;
    }
    
    /**
     * Get polygon center (YOUR EXISTING CODE)
     */
    private function getPolygonCenter($geojson): array
    {
        $coordinates = match ($geojson['type'] ?? null) {
            'Polygon' => $geojson['coordinates'][0],
            'MultiPolygon' => $geojson['coordinates'][0][0],
            default => []
        };

        $sumLat = 0;
        $sumLng = 0;
        $count = count($coordinates) - 1;

        for ($i = 0; $i < $count; $i++) {
            $sumLng += $coordinates[$i][0];
            $sumLat += $coordinates[$i][1];
        }

        return [
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count
        ];
    }
    
    /**
     * Get generation progress
     */
    public function getProgress(): array
    {
        $total = Parcel::count();
        
        return [
            'total' => $total,
            'pending' => ParcelImage::where('status', 'pending')->count(),
            'processing' => ParcelImage::where('status', 'processing')->count(),
            'completed' => ParcelImage::where('status', 'completed')->count(),
            'failed' => ParcelImage::where('status', 'failed')->count(),
            'percentage' => $total > 0 
                ? round((ParcelImage::where('status', 'completed')->count() / $total) * 100, 2)
                : 0
        ];
    }
}