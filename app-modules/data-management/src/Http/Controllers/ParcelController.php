<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;

use Modules\DataManagement\Models\Parcel;
use Modules\DataManagement\Models\ParcelStyle;
use Modules\DataManagement\Models\ParcelSymbology;
use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Services\QueryService;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Shapefile\ShapefileReader;
use Shapefile\ShapefileException;
use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;
use App\Helpers\ExtraHelper;
use Modules\DataManagement\Models\Form;

use Modules\DataManagement\Http\Controllers\ParcelSymbologyController;

class ParcelController
{
     protected $query;

    public function __construct(

        QueryService $query,
        )
    {
        $this->query = $query->getQuery();
    }
    public function index()
    {
        $files = Storage::disk('public')->files('uploads/shapefile/parcel');

        // Extract only the filenames
        $filenames = array_map(function($file) {
            return basename($file);
        }, $files);


        $allFiles = [];

        foreach ($filenames as $key => $value) {

            $parcels = Parcel::where('shap_file_name', $value)->get();

            $countWithImage = $parcels->filter(function($parcel) {
                return $parcel->image !== null;
            })->count();
            if ($parcels) {
                $allFiles[] = ['file' => $value, 'total_parcels' => count($parcels), 'total_images' => $countWithImage];
            } else {
                $allFiles[] = ['file' => $value, 'total_parcels' => 0, 'total_images' => 0];
            }
        }

        return response()->json($allFiles);
    }

    public function download($shapefile)
    {
        $disk = Storage::disk('public');
        $path = 'uploads/shapefile/parcel/' . $shapefile;
        
        if (!$disk->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($disk->path($path), $shapefile);
    }

    public function removeImages($shapefile)
    {


        // Delete database record
         $parcels = Parcel::where('shap_file_name', $shapefile)->get();

        foreach ($parcels as $parcel) {
            if ($parcel->image) {
                $parcel->image->delete(); // triggers deleting for each parcel
            }
        }
        return response()->json(['message' => 'Images deleted successfully.'], 200);
    }

    public function destroy($shapefile)
    {
         // Delete database record
        $parcels = Parcel::where('shap_file_name', $shapefile)->get();

        foreach ($parcels as $parcel) {
            $parcel->delete(); // triggers deleting for each parcel
        }

        // Storage disk
        $disk = Storage::disk('public');
        $path = 'uploads/shapefile/parcel/' . $shapefile;

        // Delete file if exists
        if ($disk->exists($path)) {
            $disk->delete($path);
            return response()->json(['message' => 'Shapefile deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Shapefile file not found.'], 404);
    }
    /**
     * Upload and process shapefile with reprojection to WGS84
     */
    public function uploadShapefile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200' // 50MB max
        ]);

        try {
            $file = $request->file('file');

            $filename = ExtraHelper::generateFileName($file);

            // Store file
            $path = Storage::putFileAs('uploads/shapefile/parcel', $file, $filename);
            $zipPath = Storage::path($path);
            $extractPath = Storage::path('temp/shapefile_' . uniqid());
            
            mkdir($extractPath, 0777, true);

            // Extract zip
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Could not open zip file');
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // Find shapefile
            $shapefile = $this->findShapefile($extractPath);
            if (!$shapefile) {
                throw new \Exception('No .shp file found');
            }

            // Parse and reproject shapefile
            $features = $this->parseShapefileWithReprojection($shapefile, $extractPath);
            
            $imported = [];
            $stats = [
                'total' => count($features),
                'with_parcel_code' => 0,
                'with_landuse' => 0,
                'with_province' => 0
            ];

            foreach ($features as $feature) {
                // Extract parcel code from attributes (try different field names)
                $parcelCode = $feature['properties']['FINALL_COD'] ?? 
                              $feature['properties']['CODE_GIS'] ??
                             $feature['properties']['PARCEL_ID'] ?? 
                             $feature['properties']['PARCEL'] ??
                             $feature['properties']['CODE'] ?? 
                             $feature['properties']['ID'] ?? 
                             $feature['properties']['FID'] ??
                             uniqid('PARCEL_');
                
                // Calculate area if available
                $area = $feature['properties']['AREA'] ?? 
                       $feature['properties']['SHAPE_AREA'] ?? 
                       $feature['properties']['SHAPE_Length'] ??
                       null;
                
                // Update stats
                if ($parcelCode != 'PARCEL_' . substr($parcelCode, -5)) {
                    $stats['with_parcel_code']++;
                }
                
                $landUseType = $feature['properties']['LANDUSE'] ?? 
                              $feature['properties']['LU'] ??
                              $feature['properties']['TYPE'] ?? null;
                if ($landUseType) $stats['with_landuse']++;
                
                $province = $feature['properties']['PROVINCE'] ?? 
                           $feature['properties']['PROV'] ??
                           $feature['properties']['STATE'] ?? null;
                if ($province) $stats['with_province']++;
                
                $parcel = Parcel::updateOrCreate(
                    ['parcel_code' => $parcelCode],
                    [
                        'geometry' => $feature['geometry'], // Already in WGS84
                        'attributes' => $feature['properties'],
                        'land_use_type' => $landUseType,
                        'province' => $province,
                        'district' => $feature['properties']['DISTRICT'] ?? 
                                     $feature['properties']['DIST'] ?? null,
                        'village' => $feature['properties']['VILLAGE'] ?? 
                                    $feature['properties']['VILL'] ??
                                    $feature['properties']['VILL_CODE'] ?? null,
                        'area_sqm' => is_numeric($area) ? floatval($area) : null,
                        'shap_file_name' => $filename
                    ]
                );
                
                $imported[] = $parcel->id;
            }

            // Cleanup
            $this->deleteDirectory($extractPath);
            // Storage::delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Successfully imported ' . count($imported) . ' parcels',
                'stats' => $stats,
                'count' => count($imported)
            ]);

        } catch (ShapefileException $e) {
            Log::error('Shapefile parsing error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to parse shapefile: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Shapefile upload error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    

       /**
     * Get active style
     */
    public function getStyle()
    {
        try {
            $style = ParcelStyle::getActiveStyle();
            
            return response()->json($style);
            
        } catch (\Exception $e) {
            Log::error('Error fetching style: ' . $e->getMessage());
            
            // Return default style if error
            return response()->json([
                'name' => 'default',
                'selected_color' => '#FF0000',
                'selected_weight' => 3,
                'selected_style' => 'solid',
                'selected_opacity' => 1.0,
                'other_color' => '#CCCCCC',
                'other_weight' => 1,
                'other_style' => 'solid',
                'other_opacity' => 0.3,
                'is_active' => true
            ]);
        }
    }

     public function updateStyle(Request $request)
    {
        $request->validate([
            'selected_color' => 'required|string',
            'selected_weight' => 'required|integer|min:1|max:10',
            'selected_style' => 'required|in:solid,dashed,dotted',
            'selected_opacity' => 'required|numeric|min:0|max:1',
            'other_color' => 'required|string',
            'other_weight' => 'required|integer|min:1|max:10',
            'other_style' => 'required|in:solid,dashed,dotted',
            'other_opacity' => 'required|numeric|min:0|max:1'
        ]);

        $style = ParcelStyle::getActiveStyle();
        $style->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Style updated successfully',
            'style' => $style
        ]);
    }

        /**
     * Parse shapefile and reproject coordinates to WGS84
     */
    private function parseShapefileWithReprojection($shapefilePath, $extractPath)
    {
        try {
            Log::info('Attempting to parse shapefile: ' . $shapefilePath);
            
            // Initialize proj4
            $proj4 = new Proj4php();
            
            // Target projection is always WGS84
            $targetProj = new Proj('EPSG:4326', $proj4);
            
            // Check if PRJ file exists to determine source projection
            $prjFile = $this->findFileByExtension($extractPath, 'prj');
            $sourceProj = null;
            
            if ($prjFile) {
                $wkt = file_get_contents($prjFile);
                Log::info('Found PRJ file with projection: ' . $wkt);
                
                // Parse WKT to determine EPSG code
                // For Kunduz area, it's likely UTM Zone 42N (EPSG:32642)
                if (strpos($wkt, 'UTM') !== false || strpos($wkt, 'Meter') !== false) {
                    if (strpos($wkt, 'Zone 42') !== false) {
                        $sourceProj = new Proj('EPSG:32642', $proj4); // UTM Zone 42N
                        Log::info('Detected UTM Zone 42N (EPSG:32642)');
                    } elseif (strpos($wkt, 'Zone 41') !== false) {
                        $sourceProj = new Proj('EPSG:32641', $proj4); // UTM Zone 41N
                        Log::info('Detected UTM Zone 41N (EPSG:32641)');
                    } elseif (strpos($wkt, 'Zone 43') !== false) {
                        $sourceProj = new Proj('EPSG:32643', $proj4); // UTM Zone 43N
                        Log::info('Detected UTM Zone 43N (EPSG:32643)');
                    }
                }
            }
            
            // If no source projection found, try to detect from coordinates
            if (!$sourceProj) {
                // Get first record to check coordinate range
                $testReader = new ShapefileReader($shapefilePath);
                foreach ($testReader as $testRecord) {
                    if ($testRecord->isDeleted()) continue;
                    
                    $testGeojson = json_decode($testRecord->getGeoJSON(), true);
                    $firstCoord = $this->extractFirstCoordinate($testGeojson['coordinates']);
                    
                    if ($firstCoord) {
                        list($x, $y) = $firstCoord;
                        
                        // Check if coordinates are in UTM range (large numbers)
                        if ($x > 100000 && $x < 1000000 && $y > 0 && $y < 10000000) {
                            // Likely UTM - determine zone from longitude
                            // For Kunduz area (around 69°E), UTM Zone 42N covers 66°E to 72°E
                            $lon = 69.0; // Approximate
                            $zone = floor(($lon + 180) / 6) + 1;
                            $sourceProj = new Proj('EPSG:326' . $zone, $proj4);
                            Log::info("Detected UTM Zone {$zone}N from coordinate range");
                        } else {
                            // Assume already in WGS84
                            $sourceProj = $targetProj;
                            Log::info('Coordinates appear to be in WGS84 already');
                        }
                    }
                    break;
                }
            }
            
            // Default to UTM Zone 42N if still not determined
            if (!$sourceProj) {
                Log::warning('Could not determine source projection, assuming UTM Zone 42N');
                $sourceProj = new Proj('EPSG:32642', $proj4);
            }
            
            $features = [];
            
            // Create Shapefile reader
            $shapefile = new ShapefileReader($shapefilePath);
            
            // Read all records
            $count = 0;
            foreach ($shapefile as $record) {
                if ($record->isDeleted()) {
                    continue;
                }
                
                // Get GeoJSON
                $geojson = json_decode($record->getGeoJSON(), true);
                
                // Get attributes
                $attributes = $record->getDataArray();
                
                // Reproject coordinates to WGS84 if needed
                if ($sourceProj != $targetProj && isset($geojson['coordinates'])) {
                    $geojson['coordinates'] = $this->reprojectGeoJSONCoordinates(
                        $geojson['coordinates'], 
                        $sourceProj, 
                        $targetProj,
                        $proj4
                    );
                    
                    // Update bbox if present
                    if (isset($geojson['bbox'])) {
                        unset($geojson['bbox']); // Remove bbox as it's no longer valid
                    }
                }
                
                $features[] = [
                    'geometry' => $geojson,
                    'properties' => $attributes
                ];
                
                $count++;
            }
            
            Log::info("Successfully parsed and reprojected {$count} features to WGS84");
            return $features;
            
        } catch (\Exception $e) {
            Log::error('Shapefile parsing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find file by extension in directory
     */
    private function findFileByExtension($directory, $extension)
    {
        $files = scandir($directory);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === strtolower($extension)) {
                return $directory . '/' . $file;
            }
        }
        return null;
    }

        /**
     * Recursively reproject GeoJSON coordinates
     */
    private function reprojectGeoJSONCoordinates($coords, $sourceProj, $targetProj, $proj4)
    {
        if (is_array($coords) && isset($coords[0]) && is_numeric($coords[0])) {
            // This is a coordinate pair [x, y]
            try {
                // Create point with [x, y]
                $point = new Point($coords[0], $coords[1]);
                
                // Transform to target projection
                $transformed = $proj4->transform($sourceProj, $targetProj, $point);
                
                // Return as [longitude, latitude] (GeoJSON standard)
                return [$transformed->x, $transformed->y];
                
            } catch (\Exception $e) {
                Log::error('Error reprojecting point: ' . $e->getMessage());
                return $coords; // Return original if reprojection fails
            }
        } elseif (is_array($coords)) {
            // Recursively process nested arrays
            $result = [];
            foreach ($coords as $key => $value) {
                $result[$key] = $this->reprojectGeoJSONCoordinates($value, $sourceProj, $targetProj, $proj4);
            }
            return $result;
        }
        
        return $coords;
    }


     /**
     * Extract first coordinate from nested array
     */
    private function extractFirstCoordinate($coords)
    {
        if (is_array($coords) && isset($coords[0]) && is_numeric($coords[0])) {
            return [$coords[0], $coords[1]];
        } elseif (is_array($coords) && isset($coords[0])) {
            return $this->extractFirstCoordinate($coords[0]);
        }
        return null;
    }

    

       /**
     * Get all parcels as GeoJSON (already in WGS84)
     */
    public function getParcels(Request $request)
    {
        // try {
            $parcels = [];
            
            if (!isset($request->ids[0]) || isset($request->ids[1])) {
                $parcels = Parcel::all();
            } else {
                $ps = ParcelSymbology::find($request->ids[0]);

                $query = Submission::with($this->query)->with('extraAttributes');

                $query->whereHas('projects', function ($q) use ($ps) {
                    $q->where('projects.id', $ps->project_id);
                });
            
                ParcelSymbologyController::getSearchData($query, $ps->query_structure);
                 $data = $query->get();
                $form = Form::find($data[0]->dm_form_id);
                $dataObject = json_decode($form->raw_schema);
                $survey = $dataObject->asset->content->survey;
                $choices = $dataObject->asset->content->choices;

                $pCodes = [];
                
                foreach ($data as $key => $submission) {
                    $guzar;
                    $block;
                    $house;
                    foreach ($choices as $key => $value) {
                        if (isset($value->name) && $value->name === $submission->extraAttributesJson['guzar_number']) {
                            if (isset($value->label[0])) {
                                $guzar = substr($value->label[0], 1);
                            }
                        }
                        if (isset($value->name) && $value->name === $submission->sourceInformation->block_number) {
                            if (isset($value->label[0])) {
                                $block = $value->label[0];
                            }
                        }
                        if (isset($value->name) && $value->name === $submission->sourceInformation->house_number) {
                            if (isset($value->label[0])) {
                                $house = $value->label[0];
                            }
                        }
                    }
                    $pCodes[] = implode('-', array_filter([
                        $submission->sourceInformation->province_code ?? null,
                        $submission->sourceInformation->city_code ?? null,
                        $submission->sourceInformation->district_code ?? null,
                        $guzar ?? null,
                        $block ?? null,
                        $house ?? null,
                    ]));         
                            
                }

     

                $parcels = Parcel::whereIn('parcel_code', $pCodes)->get();

             }
            
            $features = $parcels->map(function($parcel) {
                // Geometry is already in WGS84 from upload
                return [
                    'type' => 'Feature',
                    'geometry' => $parcel->geometry,
                    'properties' => array_merge(
                        $parcel->attributes,
                        [
                            'parcel_code' => $parcel->parcel_code,
                            'house_code' => $parcel->house_code,
                            'is_linked' => $parcel->is_linked,
                            'land_use_type' => $parcel->land_use_type,
                            'province' => $parcel->province,
                            'district' => $parcel->district,
                            'village' => $parcel->village,
                            'area_sqm' => $parcel->area_sqm,
                            'id' => $parcel->id
                        ]
                    )
                ];
            });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
                'total' => $features->count(),
                'crs' => [
                    'type' => 'name',
                    'properties' => [
                        'name' => 'EPSG:4326'
                    ]
                ]
            ]);
            
        // } catch (\Exception $e) {
        //     Log::error('Error fetching parcels: ' . $e->getMessage());
        //     return response()->json(['error' => 'Failed to fetch parcels'], 500);
        // }
    }

    public function getParcel($code)
    {
        $parcel = Parcel::where('parcel_code', $code)->first();
        
        if (!$parcel) {
            return response()->json(['error' => 'Parcel not found'], 404);
        }

        return response()->json([
            'parcel' => $parcel,
            'center' => $parcel->center
        ]);
    }

        public function linkParcel(Request $request)
    {
        $request->validate([
            'parcel_code' => 'required|string',
            'house_code' => 'required|string'
        ]);

        $parcel = Parcel::where('parcel_code', $request->parcel_code)->first();
        
        if (!$parcel) {
            return response()->json(['error' => 'Parcel not found'], 404);
        }

        $parcel->update([
            'house_code' => $request->house_code,
            'is_linked' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parcel linked successfully'
        ]);
    }
    private function findShapefile($dir)
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'shp') {
                return $dir . '/' . $file;
            }
        }
        return null;
    }

     private function parseShapefile($path)
    {
        $features = [];
        $shapefile = new ShapefileReader($path);
        
        foreach ($shapefile as $record) {
            if ($record->isDeleted()) continue;
            
            $geojson = json_decode($record->getGeoJSON(), true);
            $attributes = $record->getDataArray();
            
            $features[] = [
                'geometry' => $geojson,
                'properties' => $attributes
            ];
        }
        
        return $features;
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    // protected function getFileName($file) {
    //     // Get original name without extension
    //     $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    //     // Get extension
    //     $extension = $file->getClientOriginalExtension();

    //     // Create unique filename
    //     $filename = $name . '_' . time() . '.' . $extension;

    //     return $filename;
    // }
}
