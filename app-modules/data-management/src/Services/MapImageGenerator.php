<?php
// app/Services/MapImageGenerator.php

namespace App\Services;

use App\Models\House;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MapImageGenerator
{
    protected $googleMapsApiKey;
    
    public function __construct()
    {
        $this->googleMapsApiKey = config('services.google.maps_api_key');
    }
    
    /**
     * Generate map image for a single house
     */
    public function generateMapImage(House $house): ?string
    {
        try {
            if (!$house->boundary_geojson) {
                Log::warning("House {$house->id} has no boundary data");
                return null;
            }
            
            // Build the Static Maps URL
            $mapUrl = $this->buildStaticMapUrl($house);
            
            // Download the image
            $response = Http::get($mapUrl);
            
            if (!$response->successful()) {
                Log::error("Failed to download map image for house {$house->id}");
                return null;
            }
            
            // Generate filename
            $filename = 'maps/house-' . $house->house_code . '-' . time() . '.png';
            
            // Save image to storage
            Storage::disk('public')->put($filename, $response->body());
            
            return $filename;
            
        } catch (\Exception $e) {
            Log::error("Map generation failed for house {$house->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Build Google Static Maps URL with proper boundaries
     */
    private function buildStaticMapUrl(House $selectedHouse): string
    {
        $baseUrl = 'https://maps.googleapis.com/maps/api/staticmap';
        
        // Get polygon center
        $center = $this->getPolygonCenter($selectedHouse->boundary_geojson);
        
        // Build parameters
        $params = [
            'center' => $center['lat'] . ',' . $center['lng'],
            'zoom' => 19,
            'size' => '1024x768',
            'maptype' => 'satellite',
            'format' => 'png32',
            'key' => $this->googleMapsApiKey,
        ];
        
        // Add the selected house path (clear border, semi-transparent fill)
        $selectedPath = $this->buildPathForHouse($selectedHouse, true);
        if ($selectedPath) {
            $params['path'] = $selectedPath;
        }
        
        // Add all other houses with 50% transparency
        $otherHouses = House::whereNotNull('boundary_geojson')
            ->where('id', '!=', $selectedHouse->id)
            ->get();
        
        $otherPaths = [];
        foreach ($otherHouses as $house) {
            $path = $this->buildPathForHouse($house, false);
            if ($path) {
                $otherPaths[] = $path;
            }
        }
        
        // Build query string
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        // Add other paths as additional parameters
        if (!empty($otherPaths)) {
            $queryString .= '&path=' . implode('&path=', array_map('urlencode', $otherPaths));
        }
        
        return $baseUrl . '?' . $queryString;
    }
    
    /**
     * Build path string for a house
     */
    private function buildPathForHouse(House $house, bool $isSelected): ?string
    {
        if (!$house->boundary_geojson) {
            return null;
        }
        
        $points = $this->geojsonToPathString($house->boundary_geojson);
        
        if ($isSelected) {
            // Selected house: colored border, semi-transparent fill
            $color = str_replace('#', '0x', $house->outline_color ?? '#FF0000');
            $weight = $house->outline_thickness ?? 3;
            return "color:{$color}|weight:{$weight}|fillcolor:0xFFFFFF33|{$points}";
        } else {
            // Other houses: gray border, 50% white fill
            return "color:0xCCCCCCFF|weight:1|fillcolor:0xFFFFFF80|{$points}";
        }
    }
    
    
    /**
     * Convert GeoJSON to Google Maps path string
     */
    private function geojsonToPathString($geojson): string
    {
        if ($geojson['type'] === 'Polygon') {
            $coordinates = $geojson['coordinates'][0];
        } elseif ($geojson['type'] === 'MultiPolygon') {
            $coordinates = $geojson['coordinates'][0][0];
        } else {
            return '';
        }
        
        return collect($coordinates)
            ->map(fn($coord) => $coord[1] . ',' . $coord[0])
            ->implode('|');
    }
    
    /**
     * Calculate polygon center
     */
    private function getPolygonCenter($geojson): array
    {
        if ($geojson['type'] === 'Polygon') {
            $coordinates = $geojson['coordinates'][0];
        } elseif ($geojson['type'] === 'MultiPolygon') {
            $coordinates = $geojson['coordinates'][0][0];
        } else {
            return ['lat' => 0, 'lng' => 0];
        }
        
        $sumLat = 0;
        $sumLng = 0;
        $count = count($coordinates);
        
        foreach ($coordinates as $coord) {
            $sumLat += $coord[1];
            $sumLng += $coord[0];
        }
        
        return [
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count
        ];
    }
}