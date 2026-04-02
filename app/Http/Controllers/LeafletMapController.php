<?php
// app/Http/Controllers/LeafletMapController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Parcel;

class LeafletMapController extends Controller
{
    protected $mapWidth = 800;
    protected $mapHeight = 570;
    protected $zoom = 20;
    protected $tileSize = 256; // Keep tile size constant
    protected $centerLat;
    protected $centerLng;
    
    // Available font sizes in GD (1-5)
    protected $fontSizes = [1, 2, 3, 4, 5];
    
    /**
     * Generate map with parcels
     */
    public function get(Request $request)
    {
        $selectedHouseCode = $request->input('house_code', '19-1906-05-003-005');
        
        // Build the map with parcels
        $imagePath = $this->buildMapWithParcels($selectedHouseCode);
        
        if (!$imagePath) {
            Log::error("Failed to generate map");
            return response()->json(['error' => 'Failed to generate map'], 500);
        }
        
        return response()->json([
            'success' => true,
            'filename' => basename($imagePath),
            'url' => asset('storage/' . $imagePath)
        ]);
    }
    
    /**
     * Get parcels from database
     */
    private function getParcelsFromDatabase(): array
    {
        // Get parcels from database
        $parcels = Parcel::all();
        
        $data = [];
        foreach ($parcels as $parcel) {
            if ($parcel->geometry) {
                $data[] = [
                    'code' => $parcel->parcel_code,
                    'geojson' => $parcel->geometry
                ];
            }
        }
        
        Log::info("Retrieved " . count($data) . " parcels from database");
        return $data;
    }
    
    /**
     * Build map with parcels
     */
    private function buildMapWithParcels(string $selectedHouseCode)
    {
        // Get parcels from database
        $parcels = $this->getParcelsFromDatabase();
        
        if (empty($parcels)) {
            Log::error("No parcels found in database");
            return null;
        }
        
        // Find selected parcel
        $selectedParcel = null;
        foreach ($parcels as $parcel) {
            if ($parcel['code'] === $selectedHouseCode) {
                $selectedParcel = $parcel;
                break;
            }
        }
        
        if (!$selectedParcel) {
            $selectedParcel = $parcels[0];
        }
        
        // Calculate center from selected parcel
        $center = $this->getPolygonCenter($selectedParcel['geojson']);
        $this->centerLat = $center['lat'];
        $this->centerLng = $center['lng'];
        
        Log::info("Center coordinates: lat={$center['lat']}, lng={$center['lng']}");
        
        // Calculate world coordinates for center
        $centerWorldX = $this->latLngToWorldX($this->centerLng, $this->zoom);
        $centerWorldY = $this->latLngToWorldY($this->centerLat, $this->zoom);
        
        // Calculate which tiles we need based on world coordinates
        $minWorldX = $centerWorldX - ($this->mapWidth / 2);
        $maxWorldX = $centerWorldX + ($this->mapWidth / 2);
        $minWorldY = $centerWorldY - ($this->mapHeight / 2);
        $maxWorldY = $centerWorldY + ($this->mapHeight / 2);
        
        // Convert world coordinates to tile numbers
        $minTileX = floor($minWorldX / $this->tileSize);
        $maxTileX = floor($maxWorldX / $this->tileSize);
        $minTileY = floor($minWorldY / $this->tileSize);
        $maxTileY = floor($maxWorldY / $this->tileSize);
        
        $tilesPerRow = $maxTileX - $minTileX + 1;
        $tilesPerCol = $maxTileY - $minTileY + 1;
        
        // Create base image (ONCE!)
        $mapImage = imagecreatetruecolor($this->mapWidth, $this->mapHeight);
        
        // Fill with light gray background
        $bgColor = imagecolorallocate($mapImage, 240, 240, 240);
        imagefill($mapImage, 0, 0, $bgColor);
        
        // Download and place tiles using world coordinates (NEW SYSTEM)
        $tileDownloaded = $this->downloadTilesWorld(
            $mapImage, 
            $minTileX, 
            $minTileY, 
            $tilesPerRow, 
            $tilesPerCol,
            $centerWorldX,
            $centerWorldY
        );
        
        Log::info("Tile download status: " . ($tileDownloaded ? "Success" : "Failed"));
        
        // Draw all parcels (non-selected first, then selected on top)
        foreach ($parcels as $parcel) {
            $isSelected = ($parcel['code'] === $selectedParcel['code']);
            if (!$isSelected) {
                $this->drawPolygon($mapImage, $parcel['geojson'], $isSelected);
            }
        }
        
        // Draw selected parcel on top
        $this->drawPolygon($mapImage, $selectedParcel['geojson'], true);
        
        // Add text labels for ALL parcels
        foreach ($parcels as $parcel) {
            $this->drawLabelInsidePolygon($mapImage, $parcel['geojson'], $parcel['code']);
        }
        
        $this->drawHeader($mapImage, $selectedParcel, $selectedParcel['code']);
        
        // Save the final image as JPEG
        $filename = 'maps/parcel-' . $selectedParcel['code'] . '-' . time() . '.jpg';
        $path = Storage::disk('public')->path($filename);
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        // Save as JPEG with quality 90
        imagejpeg($mapImage, $path, 90);
        imagedestroy($mapImage);
        
        return $filename;
    }
    
    /**
     * Download tiles using world coordinates
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
                            // Calculate tile's world coordinates
                            $tileWorldX = $tileX * $this->tileSize;
                            $tileWorldY = $tileY * $this->tileSize;
                            
                            // Calculate position on map
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
        
        // Fallback to OSM if needed
        if (!$tileDownloaded) {
            $tileDownloaded = $this->downloadOSMTilesWorld($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $centerWorldX, $centerWorldY);
        }
        
        return $tileDownloaded;
    }
    
    /**
     * Download OpenStreetMap tiles using world coordinates
     */
    private function downloadOSMTilesWorld($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $centerWorldX, $centerWorldY)
    {
        $tileDownloaded = false;
        
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
                            $tileDownloaded = true;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        return $tileDownloaded;
    }
    
    /**
     * Draw label inside polygon with optimal font size
     */
    private function drawLabelInsidePolygon($image, $geojson, string $text)
    {
        $coordinates = match ($geojson['type']) {
            'Polygon' => $geojson['coordinates'][0],
            'MultiPolygon' => $geojson['coordinates'][0][0],
            default => []
        };
        
        if (empty($coordinates)) {
            return;
        }
        
        // Get polygon bounds in pixels
        $bounds = $this->getPolygonBounds($coordinates);
        
        if (!$bounds) {
            return;
        }
        
        // Calculate center point
        $centerX = ($bounds['minX'] + $bounds['maxX']) / 2;
        $centerY = ($bounds['minY'] + $bounds['maxY']) / 2;
        
        // Only draw if center is within image bounds
        if ($centerX < 0 || $centerX > $this->mapWidth || $centerY < 0 || $centerY > $this->mapHeight) {
            return;
        }
        
        // Calculate available space
        $availableWidth = $bounds['maxX'] - $bounds['minX'];
        $availableHeight = $bounds['maxY'] - $bounds['minY'];
        
        // Find the largest font that fits
        $fontSize = $this->findBestFontSize($text, $availableWidth, $availableHeight);
        
        // Draw the label
        $this->drawTextLabel($image, $text, (int)$centerX, (int)$centerY, $fontSize);
    }
    
    /**
     * Get polygon bounds in pixels
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
            
            // Only consider points that are reasonably near the image
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
    
    /**
     * Find the best font size that fits within the available space
     */
    private function findBestFontSize(string $text, float $availableWidth, float $availableHeight): int
    {
        $textLength = strlen($text);
        
        // Try largest to smallest
        foreach (array_reverse($this->fontSizes) as $size) {
            $charWidth = imagefontwidth($size);
            $charHeight = imagefontheight($size);
            
            $textWidth = $textLength * $charWidth;
            $textHeight = $charHeight;
            
            // Add 20% padding for safety
            if ($textWidth < $availableWidth * 0.8 && $textHeight < $availableHeight * 0.8) {
                return $size;
            }
        }
        
        return 1; // Smallest font as fallback
    }
    
    /**
     * Draw text label with configurable font size
     */
    private function drawTextLabel($image, string $text, int $x, int $y, int $font)
    {
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        
        $textWidth = strlen($text) * $charWidth;
        $textHeight = $charHeight;
        
        // Calculate position to center text
        $textX = $x - ($textWidth / 2);
        $textY = $y - ($textHeight / 2);
        
        // Ensure text stays within image bounds
        $textX = max(2, min($this->mapWidth - $textWidth - 2, (int)$textX));
        $textY = max(2, min($this->mapHeight - $textHeight - 2, (int)$textY));
        
        // Draw with white text and thick black outline
        $whiteColor = imagecolorallocate($image, 255, 255, 255);
        $blackColor = imagecolorallocate($image, 0, 0, 0);
        
        // Draw thick black outline for better visibility
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dy = -2; $dy <= 2; $dy++) {
                if ($dx != 0 || $dy != 0) {
                    imagestring($image, $font, $textX + $dx, $textY + $dy, $text, $blackColor);
                }
            }
        }
        
        // Draw white text
        imagestring($image, $font, $textX, $textY, $text, $whiteColor);
    }
    
    /**
     * Draw polygon on map image
     */
    private function drawPolygon($image, $geojson, bool $isSelected)
    {
        $coordinates = match ($geojson['type']) {
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
        
        if ($isSelected) {
            // SELECTED PARCEL: Thick red border only, no fill

            $borderColor = imagecolorallocatealpha($image, 255, 255, 0, 0); // Bright red
            $borderWeight = 4;
            
            for ($i = 0; $i < $borderWeight; $i++) {
                $offsetPoints = [];
                foreach ($points as $point) {
                    $offsetPoints[] = (int)($point['x'] + $i);
                    $offsetPoints[] = (int)($point['y'] + $i);
                }
                imagepolygon($image, $offsetPoints, count($points), $borderColor);
            }
        } else {
            // OTHER PARCELS: White 50% opacity fill with thin gray border
            $fillColor = imagecolorallocatealpha($image, 255, 255, 255, 80); // White with 50% opacity
            $borderColor = imagecolorallocatealpha($image, 0, 0, 0, 0); // Gray border
            $borderWeight = 2;
            
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
     * Convert lat/lng to pixel coordinates on the final map image
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
     * Convert lng to world X coordinate
     */
    private function latLngToWorldX($lng, $zoom)
    {
        return (($lng + 180) / 360) * pow(2, $zoom) * $this->tileSize;
    }
    
    /**
     * Convert lat to world Y coordinate
     */
    private function latLngToWorldY($lat, $zoom)
    {
        $sinLat = sin(deg2rad($lat));
        return (0.5 - log((1 + $sinLat) / (1 - $sinLat)) / (4 * M_PI)) * pow(2, $zoom) * $this->tileSize;
    }
    
    /**
 * Draw header with north arrow, text, and logos - Exactly matching your image
 */
private function drawHeader($image, $parcel, $parcelCode)
{
    $width = imagesx($image);
    
    // Create a semi-transparent black background for header
    $headerHeight = 60; // Taller to accommodate two lines of text
    $headerBg = imagecolorallocatealpha($image, 0, 0, 0, 40); // Semi-transparent black
    imagefilledrectangle($image, 0, 0, $width, $headerHeight, $headerBg);
    
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    $font = 2; // Font size for text
    
    // 1. DRAW NORTH ARROW (left side)
    $this->drawNorthArrow($image, 20, 30);
    
    // 2. DRAW TEXT IN CENTER
    $line1 = "AFGHANISTAN: Nangarhar Province - Jalalabad City-PD#08 WOCH TANGI";
    $line2 = "Parcel No: " . $parcelCode;
    
    // Calculate center position for text
    $line1Width = strlen($line1) * imagefontwidth($font);
    $line2Width = strlen($line2) * imagefontwidth($font);
    $maxTextWidth = max($line1Width, $line2Width);
    
    $centerX = ($width / 2) - ($maxTextWidth / 2);
    
    // Draw first line
    imagestring($image, $font, (int)$centerX, 15, $line1, $whiteColor);
    
    // Draw second line
    imagestring($image, $font, (int)$centerX, 35, $line2, $whiteColor);
    
    // 3. DRAW TWO LOGOS (right side)
    $this->drawLogosFromFiles($image, $width - 100, 15);
}

/**
 * Draw north arrow/compass icon
 */
private function drawNorthArrow($image, $x, $y)
{
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    $redColor = imagecolorallocate($image, 255, 0, 0);
    
    // Draw arrow
    $arrowSize = 20;
    
    // Draw arrow shaft (vertical line)
    imageline($image, $x, $y - 10, $x, $y + 10, $whiteColor);
    
    // Draw arrow head (triangle)
    $points = [
        $x, $y - 15,  // Top point
        $x - 5, $y - 5, // Bottom left
        $x + 5, $y - 5  // Bottom right
    ];
    imagefilledpolygon($image, $points, 3, $redColor);
    
    // Draw "N" letter
    imagestring($image, 1, $x - 3, $y - 25, "N", $whiteColor);
}

/**
 * Draw two logos on the right side
 */
private function drawLogos($image, $startX, $startY)
{
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    
    // First logo - simple rectangle with text (replace with actual logo if you have image files)
    imagerectangle($image, $startX, $startY, $startX + 30, $startY + 30, $whiteColor);
    imagestring($image, 1, $startX + 5, $startY + 10, "LOGO", $whiteColor);
    
    // Second logo - simple rectangle with text
    imagerectangle($image, $startX + 40, $startY, $startX + 70, $startY + 30, $whiteColor);
    imagestring($image, 1, $startX + 45, $startY + 10, "LOGO", $whiteColor);
}

/**
 * If you have actual logo images, use this method instead:
 */
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
     * Get polygon center from GeoJSON
     */
    private function getPolygonCenter($geojson): array
    {
        $coordinates = match ($geojson['type']) {
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
}