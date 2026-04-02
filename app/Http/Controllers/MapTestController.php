<?php
// app/Http/Controllers/LeafletMapController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Parcel;

class MapTestController extends Controller
{
    protected $tileSize = 256;
    protected $mapWidth = 500;
    protected $mapHeight = 500;
    protected $zoom = 19;
    
    // Available font sizes in GD (1-5)
    protected $fontSizes = [1, 2, 3, 4, 5];
    
    /**
     * Generate map with parcels
     */
    public function get(Request $request)
    {
        $selectedHouseCode = $request->input('house_code', '19-1906-05-003-003');
        
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
        
        Log::info("Center coordinates: lat={$center['lat']}, lng={$center['lng']}");
        
        // Calculate which tiles we need (using XYZ format - same as Leaflet)
        $tileInfo = $this->latLngToTileNumber($center['lat'], $center['lng'], $this->zoom);
        
        // Calculate pixel offset within the center tile
        $pixelCoords = $this->latLngToPixelInTile($center['lat'], $center['lng'], $this->zoom);
        $tileInfo['offsetX'] = $pixelCoords['x'];
        $tileInfo['offsetY'] = $pixelCoords['y'];
        
        // Calculate how many tiles we need to cover the map
        $tilesPerRow = ceil($this->mapWidth / $this->tileSize) + 1;
        $tilesPerCol = ceil($this->mapHeight / $this->tileSize) + 1;
        
        $startX = $tileInfo['x'] - floor($tilesPerRow / 2);
        $startY = $tileInfo['y'] - floor($tilesPerCol / 2);
        
        // Create base image
        $mapImage = imagecreatetruecolor($this->mapWidth, $this->mapHeight);
        
        // Fill with light gray background
        $bgColor = imagecolorallocate($mapImage, 240, 240, 240);
        imagefill($mapImage, 0, 0, $bgColor);
        
        // Use Google Satellite
        $tileDownloaded = $this->downloadTiles($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $tileInfo);
        
        Log::info("Tile download status: " . ($tileDownloaded ? "Success" : "Failed"));
        
        // If no tiles downloaded, use OpenStreetMap as fallback
        if (!$tileDownloaded) {
            Log::warning("Google Satellite failed, trying OpenStreetMap");
            $tileDownloaded = $this->downloadOSMTiles($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $tileInfo);
        }
        
        // Draw all parcels
        foreach ($parcels as $parcel) {
            $isSelected = ($parcel['code'] === $selectedParcel['code']);
            $this->drawPolygon($mapImage, $parcel['geojson'], $isSelected, $tileInfo);
        }
        
        // Add text labels for ALL parcels with dynamic font sizing
        foreach ($parcels as $parcel) {
            $this->drawLabelInsidePolygon($mapImage, $parcel['geojson'], $parcel['code'], $tileInfo);
        }
        
        // Save the final image
        $filename = 'maps/parcels-' . time() . '.png';
        $path = Storage::disk('public')->path($filename);
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        imagepng($mapImage, $path, 9);
        imagedestroy($mapImage);
        
        return $filename;
    }
    
    /**
     * Download Google Satellite tiles
     */
    private function downloadTiles($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $tileInfo)
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
                            $destX = ($col * $this->tileSize) - $tileInfo['offsetX'];
                            $destY = ($row * $this->tileSize) - $tileInfo['offsetY'];
                            
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
        
        return $tileDownloaded;
    }
    
    /**
     * Download OpenStreetMap tiles as fallback
     */
    private function downloadOSMTiles($mapImage, $startX, $startY, $tilesPerRow, $tilesPerCol, $tileInfo)
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
                            $destX = ($col * $this->tileSize) - $tileInfo['offsetX'];
                            $destY = ($row * $this->tileSize) - $tileInfo['offsetY'];
                            
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
    private function drawLabelInsidePolygon($image, $geojson, string $text, array $tileInfo)
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
        $bounds = $this->getPolygonBounds($coordinates, $tileInfo);
        
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
    private function getPolygonBounds($coordinates, $tileInfo)
    {
        $minX = INF;
        $minY = INF;
        $maxX = -INF;
        $maxY = -INF;
        $validPoints = 0;
        
        foreach ($coordinates as $coord) {
            $pixel = $this->latLngToPixel($coord[1], $coord[0], $this->zoom, $tileInfo);
            
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
        
        $whiteColor = imagecolorallocate($image, 255, 255, 255);
        $blackColor = imagecolorallocate($image, 0, 0, 0);
        
        // Draw black outline (thicker for better visibility)
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dy = -1; $dy <= 1; $dy++) {
                imagestring($image, $font, $textX + $dx, $textY + $dy, $text, $blackColor);
            }
        }
        
        // Draw white text
        imagestring($image, $font, $textX, $textY, $text, $whiteColor);
    }
    
    /**
     * Draw polygon on map image
     */
    private function drawPolygon($image, $geojson, bool $isSelected, array $tileInfo)
    {
        $coordinates = match ($geojson['type']) {
            'Polygon' => $geojson['coordinates'][0],
            'MultiPolygon' => $geojson['coordinates'][0][0],
            default => []
        };
        
        $points = [];
        foreach ($coordinates as $coord) {
            $pixel = $this->latLngToPixel($coord[1], $coord[0], $this->zoom, $tileInfo);
            
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
            // SELECTED PARCEL: Red border only, no fill
            $borderColor = imagecolorallocatealpha($image, 255, 0, 0, 0);
            $borderWeight = 2;
            
            for ($i = 0; $i < $borderWeight; $i++) {
                $offsetPoints = [];
                foreach ($points as $point) {
                    $offsetPoints[] = (int)($point['x'] + $i);
                    $offsetPoints[] = (int)($point['y'] + $i);
                }
                imagepolygon($image, $offsetPoints, count($points), $borderColor);
            }
        } else {
            // OTHER PARCELS: White 50% opacity fill with gray border
            $fillColor = imagecolorallocatealpha($image, 255, 255, 255, 80);
            $borderColor = imagecolorallocatealpha($image, 150, 150, 150, 0);
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
     * Convert lat/lng to tile numbers (XYZ format - same as Leaflet)
     */
    private function latLngToTileNumber($lat, $lng, $zoom)
    {
        $xtile = floor((($lng + 180) / 360) * pow(2, $zoom));
        $ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * pow(2, $zoom));
        
        return ['x' => (int)$xtile, 'y' => (int)$ytile];
    }
    
    /**
     * Convert lat/lng to pixel coordinates within a tile
     */
    private function latLngToPixelInTile($lat, $lng, $zoom)
    {
        $tile = $this->latLngToTileNumber($lat, $lng, $zoom);
        
        $worldCoordX = (($lng + 180) / 360) * pow(2, $zoom) * $this->tileSize;
        $worldCoordY = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * pow(2, $zoom) * $this->tileSize;
        
        $pixelX = (int)($worldCoordX - ($tile['x'] * $this->tileSize));
        $pixelY = (int)($worldCoordY - ($tile['y'] * $this->tileSize));
        
        return ['x' => $pixelX, 'y' => $pixelY];
    }
    
    /**
     * Convert lat/lng to pixel coordinates on the final map image
     */
    private function latLngToPixel($lat, $lng, $zoom, $tileInfo)
    {
        $pixelInTile = $this->latLngToPixelInTile($lat, $lng, $zoom);
        
        $centerTileX = $tileInfo['x'];
        $centerTileY = $tileInfo['y'];
        $centerOffsetX = $tileInfo['offsetX'];
        $centerOffsetY = $tileInfo['offsetY'];
        
        $pointTile = $this->latLngToTileNumber($lat, $lng, $zoom);
        
        $tileDiffX = $pointTile['x'] - $centerTileX;
        $tileDiffY = $pointTile['y'] - $centerTileY;
        
        $pixelX = ($tileDiffX * $this->tileSize) + ($pixelInTile['x'] - $centerOffsetX) + ($this->mapWidth / 2);
        $pixelY = ($tileDiffY * $this->tileSize) + ($pixelInTile['y'] - $centerOffsetY) + ($this->mapHeight / 2);
        
        return [
            'x' => $pixelX,
            'y' => $pixelY
        ];
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