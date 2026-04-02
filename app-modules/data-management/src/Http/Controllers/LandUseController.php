<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Models\LandUse;
use Modules\DataManagement\Models\SymbologySetting;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Shapefile\ShapefileReader;
use Shapefile\ShapefileException;
use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

use App\Helpers\ExtraHelper;

class LandUseController
{
    public function index()
    {
        $files = Storage::disk('public')->files('uploads/shapefile/landuse');

        // Extract only the filenames
        $filenames = array_map(function($file) {
            return basename($file);
        }, $files);


        $allFiles = [];

        foreach ($filenames as $key => $value) {

            $landuses = LandUse::where('shap_file_name', $value)->get();

            if ($landuses) {
                $allFiles[] = ['file' => $value, 'total_landuse' => count($landuses)];
            } else {
                $allFiles[] = ['file' => $value, 'total_landuse' => 0];
            }
        }

        return response()->json($allFiles);
    }

     public function download($shapefile)
    {
        $disk = Storage::disk('public');
        $path = 'uploads/shapefile/landuse/' . $shapefile;
        
        if (!$disk->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($disk->path($path), $shapefile);
    }

    public function destroy($shapefile)
    {
         // Delete database record
        $landuses = LandUse::where('shap_file_name', $shapefile)->get();

        foreach ($landuses as $landuse) {
            $landuse->delete(); // triggers deleting for each landuse
        }

        // Storage disk
        $disk = Storage::disk('public');
        $path = 'uploads/shapefile/landuse/' . $shapefile;

        // Delete file if exists
        if ($disk->exists($path)) {
            $disk->delete($path);
            return response()->json(['message' => 'Shapefile deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Shapefile file not found.'], 404);
    }
    /**
     * Upload and process shapefile
     */
    public function uploadShapefile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200' // 50MB max
        ]);

        try {
            // Log file info for debugging
            $file = $request->file('file');
            $filename = ExtraHelper::generateFileName($file);
            
            // Store file
            $path = Storage::putFileAs('uploads/shapefile/landuse', $file, $filename);
            
            
            if (!$path) {
                throw new \Exception('Failed to store file');
            }
            
            Log::info('File stored at: ' . $path);
            
            // Get the full path using Storage
            $zipPath = Storage::path($path);
            Log::info('Full path: ' . $zipPath);
            
            // Check if file exists using Storage
            if (!Storage::exists($path)) {
                throw new \Exception('File was not stored properly');
            }
            
            // Create unique temp directory in storage
            $tempDir = 'temp/shapefile_' . uniqid();
            $extractPath = Storage::path($tempDir);
            
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
                Log::info('Created temp directory: ' . $extractPath);
            }

            // Extract zip file
            $zip = new ZipArchive;
            $zipOpenResult = $zip->open($zipPath);
            
            Log::info('Zip open result: ' . $zipOpenResult);
            
            if ($zipOpenResult !== true) {
                throw new \Exception('Could not open zip file: ' . $this->getZipErrorMessage($zipOpenResult));
            }

            Log::info('Zip contains ' . $zip->numFiles . ' files');
            
            // List files in zip for debugging
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                Log::info('ZIP contents: ' . $stat['name'] . ' (' . $stat['size'] . ' bytes)');
            }
            
            // Extract all files
            $extractResult = $zip->extractTo($extractPath);
            
            if (!$extractResult) {
                $error = error_get_last();
                Log::error('Extraction failed: ' . print_r($error, true));
                throw new \Exception('Failed to extract zip contents');
            }
            
            $zip->close();
            Log::info('Zip extracted successfully');

            // Get list of extracted files
            $extractedFiles = array_diff(scandir($extractPath), ['.', '..']);
            Log::info('Extracted files: ' . implode(', ', $extractedFiles));

            if (empty($extractedFiles)) {
                throw new \Exception('No files were extracted from the zip');
            }

            // Find the shapefile (.shp)
            $shapefile = $this->findFileByExtension($extractPath, 'shp');
            
            if (!$shapefile) {
                throw new \Exception('No .shp file found in the zip archive');
            }
            
            Log::info('Found shapefile: ' . $shapefile);

            // Check for required companion files
            $requiredFiles = ['shx', 'dbf'];
            $missingFiles = [];
            
            foreach ($requiredFiles as $ext) {
                if (!$this->findFileByExtension($extractPath, $ext)) {
                    $missingFiles[] = $ext;
                }
            }
            
            if (!empty($missingFiles)) {
                Log::warning('Missing shapefile components: ' . implode(', ', $missingFiles));
                
                // Clean up before returning error
                Storage::delete($path);
                $this->deleteDirectory($extractPath);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required files: ' . implode(', ', $missingFiles)
                ], 400);
            }

            // Parse shapefile (using sample data since GeoPHP not installed)
            $features = $this->parseShapefile($shapefile, $extractPath);
            
            if (empty($features)) {
                throw new \Exception('No features found in shapefile');
            }
            
            $imported = [];
            $defaultSymbology;
            $symbologySettings = SymbologySetting::all()->keyBy('land_use_type');
            if ($symbologySettings) {
                $defaultSymbology = $symbologySettings;
            }else {
                $defaultSymbology = SymbologySetting::getDefaultSymbology();
            }
            ;

            foreach ($features as $index => $feature) {
                try {
                    // Extract land use type from properties
                    $landUseType = $this->extractLandUseType($feature['properties']);
                    
                    Log::info('Processing feature ' . $index, ['type' => $landUseType]);
                    
                    // Apply symbology based on land use type
                    $symbology = $defaultSymbology[$landUseType] ?? [
                        'fill_color' => '#' . substr(md5($landUseType), 0, 6),
                        'border_color' => '#000000',
                        'fill_opacity' => 0.6
                    ];

                    // Create the record with GeoJSON directly
                    $landUse = LandUse::create([
                        'land_use_type' => $landUseType,
                        'geometry' => $feature['geometry'], // Store GeoJSON directly
                        'properties' => $feature['properties'],
                        'fill_color' => $symbology['fill_color'],
                        'border_color' => $symbology['border_color'],
                        'fill_opacity' => $symbology['fill_opacity'],
                        'shap_file_name' => $filename,
                    ]);


                    if (SymbologySetting::where('land_use_type', $landUseType)->doesntExist()) {
                        Log::info('Creating symbology setting for new land use type: ' . $landUseType);
                        SymbologySetting::create([
                            'land_use_type' => $landUseType,
                            'fill_color' => $symbology['fill_color'],
                            'border_color' => $symbology['border_color'],
                            'fill_opacity' => $symbology['fill_opacity']
                        ]);
    
                    }
                    
                    $imported[] = $landUse->id;
                    Log::info('Successfully imported feature ' . $index);
                    
                } catch (\Exception $e) {
                    Log::error('Error importing feature ' . $index . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Clean up - delete temp directory and zip file using Storage
            $this->deleteDirectory($extractPath);
            // Storage::delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Successfully imported ' . count($imported) . ' features',
                'count' => count($imported),
                'files_processed' => array_values($extractedFiles)
            ]);

        } catch (\Exception $e) {
            Log::error('Shapefile upload error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Clean up on error using Storage
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
            
            if (isset($extractPath) && file_exists($extractPath)) {
                $this->deleteDirectory($extractPath);
            }
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert GeoJSON to WKT (Well-Known Text)
     */
    private function geoJSONToWKT($geojsonGeometry)
    {
        if (!$geojsonGeometry) {
            return null;
        }

        $type = $geojsonGeometry['type'] ?? null;
        $coordinates = $geojsonGeometry['coordinates'] ?? [];

        switch ($type) {
            case 'Point':
                if (count($coordinates) >= 2) {
                    $lng = $coordinates[0];
                    $lat = $coordinates[1];
                    return "POINT($lng $lat)";
                }
                break;

            case 'Polygon':
                $rings = [];
                foreach ($coordinates as $ring) {
                    $points = [];
                    foreach ($ring as $coord) {
                        $points[] = $coord[0] . ' ' . $coord[1];
                    }
                    // Ensure ring is closed
                    if (count($points) > 0) {
                        $first = $points[0];
                        $last = $points[count($points) - 1];
                        if ($first !== $last) {
                            $points[] = $first;
                        }
                    }
                    $rings[] = '(' . implode(', ', $points) . ')';
                }
                return 'POLYGON(' . implode(', ', $rings) . ')';

            case 'MultiPolygon':
                $polygons = [];
                foreach ($coordinates as $polygon) {
                    $rings = [];
                    foreach ($polygon as $ring) {
                        $points = [];
                        foreach ($ring as $coord) {
                            $points[] = $coord[0] . ' ' . $coord[1];
                        }
                        if (count($points) > 0) {
                            $first = $points[0];
                            $last = $points[count($points) - 1];
                            if ($first !== $last) {
                                $points[] = $first;
                            }
                        }
                        $rings[] = '(' . implode(', ', $points) . ')';
                    }
                    $polygons[] = '(' . implode(', ', $rings) . ')';
                }
                return 'MULTIPOLYGON(' . implode(', ', $polygons) . ')';

            default:
                Log::warning('Unsupported geometry type for WKT conversion: ' . $type);
                return null;
        }
    }

    /**
     * Get all land use data as GeoJSON
     */
    /**
 * Get all land use data as GeoJSON
 */
public function getLandUse()
{
    try {
        $landUses = LandUse::all();
        
        $features = $landUses->map(function($landUse) {
            return [
                'type' => 'Feature',
                'geometry' => $landUse->geometry, // Already GeoJSON
                'properties' => array_merge(
                    $landUse->properties ?? [],
                    [
                        'land_use_type' => $landUse->land_use_type,
                        'fill_color' => $landUse->fill_color,
                        'border_color' => $landUse->border_color,
                        'fill_opacity' => $landUse->fill_opacity,
                        'id' => $landUse->id
                    ]
                )
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error fetching land use data: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch data'], 500);
    }
}

    /**
     * Get symbology settings
     */
    public function getSymbologySettings()
    {
        try {
            $settings = SymbologySetting::orderBy('sort_order')->get();
            $defaults = SymbologySetting::getDefaultSymbology();
            
            // Merge with defaults for types not yet customized
            // foreach ($defaults as $type => $default) {
            //     if (!$settings->contains('land_use_type', $type)) {
            //         $settings->push((object) array_merge(
            //             ['land_use_type' => $type],
            //             $default
            //         ));
            //     }
            // }
            
            return response()->json($settings);
            
        } catch (\Exception $e) {
            Log::error('Error fetching symbology: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch symbology'], 500);
        }
    }

    /**
     * Update symbology for a land use type
     */
    public function updateSymbology(Request $request)
    {
        // return $request;
        $request->validate([
            'land_use_type' => 'required|string',
            'fill_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'border_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'fill_opacity' => 'required|numeric|min:0|max:1'
        ]);

        try {
            // Update or create symbology setting
            $setting = SymbologySetting::updateOrCreate(
                ['land_use_type' => $request->land_use_type],
                [
                    'fill_color' => $request->fill_color,
                    'border_color' => $request->border_color,
                    'fill_opacity' => $request->fill_opacity
                ]
            );

            // Update all existing features with this land use type
            LandUse::where('land_use_type', $request->land_use_type)
                ->update([
                    'fill_color' => $request->fill_color,
                    'border_color' => $request->border_color,
                    'fill_opacity' => $request->fill_opacity
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Symbology updated successfully',
                'setting' => $setting
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating symbology: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update symbology'], 500);
        }
    }

    /**
     * Test ZIP file extraction
     */
    public function testZipExtraction(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip'
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->getPathname();
            
            $results = [
                'success' => true,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'temp_path' => $tempPath,
                'tests' => []
            ];

            // Test 1: Check if file is readable
            $results['tests']['is_readable'] = is_readable($tempPath);
            
            // Test 2: Check file permissions
            $results['tests']['permissions'] = substr(sprintf('%o', fileperms($tempPath)), -4);
            
            // Test 3: Try with ZipArchive
            $zip = new ZipArchive;
            $openResult = $zip->open($tempPath);
            $results['tests']['zip_open_result'] = $openResult;
            $results['tests']['zip_open_message'] = $this->getZipErrorMessage($openResult);
            
            if ($openResult === true) {
                $results['tests']['num_files'] = $zip->numFiles;
                
                // List all files in zip
                $files = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $files[] = [
                        'name' => $stat['name'],
                        'size' => $stat['size'],
                        'compressed_size' => $stat['comp_size'],
                        'is_directory' => ($stat['name'][strlen($stat['name'])-1] == '/')
                    ];
                }
                $results['tests']['files'] = $files;
                
                // Test 4: Try to extract first file
                if ($zip->numFiles > 0) {
                    $testExtractPath = storage_path('app/temp/test_extract_' . uniqid());
                    mkdir($testExtractPath, 0777, true);
                    
                    // Try to extract just the first file
                    $stat = $zip->statIndex(0);
                    $extractSingleResult = $zip->extractTo($testExtractPath, $stat['name']);
                    $results['tests']['extract_single'] = $extractSingleResult;
                    
                    if ($extractSingleResult && file_exists($testExtractPath . '/' . $stat['name'])) {
                        $results['tests']['extract_single_size'] = filesize($testExtractPath . '/' . $stat['name']);
                    }
                    
                    // Cleanup
                    $this->deleteDirectory($testExtractPath);
                }
                
                $zip->close();
            }

            // Test 5: Check PHP extensions
            $results['system'] = [
                'php_version' => phpversion(),
                'zip_extension_loaded' => extension_loaded('zip'),
                'gd_extension_loaded' => extension_loaded('gd'),
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time')
            ];

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Test zip error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
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
 * Parse shapefile using gasparesganga/php-shapefile with reprojection
 */
private function parseShapefile($shapefilePath, $extractPath)
{
    try {
        Log::info('Attempting to parse shapefile: ' . $shapefilePath);
        
        // Check if PRJ file exists to get source projection
        $prjFile = $this->findFileByExtension($extractPath, 'prj');
        $sourceProj = null;
        $proj4 = null;
        
        if ($prjFile) {
            $wkt = file_get_contents($prjFile);
            Log::info('Found PRJ file with projection: ' . $wkt);
            
            // Initialize proj4
            $proj4 = new Proj4php();
            
            // Define source projection (UTM Zone 42N)
            $sourceProj = new Proj('EPSG:32642', $proj4); // UTM Zone 42N
            $targetProj = new Proj('EPSG:4326', $proj4);  // WGS84
            
            Log::info('Initialized proj4 with UTM Zone 42N -> WGS84');
        } else {
            Log::warning('No PRJ file found, assuming UTM Zone 42N');
            $proj4 = new Proj4php();
            $sourceProj = new Proj('EPSG:32642', $proj4);
            $targetProj = new Proj('EPSG:4326', $proj4);
        }
        
        $features = [];
        
        // Create Shapefile reader
        $shapefile = new \Shapefile\ShapefileReader($shapefilePath);
        
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
            
            // Reproject coordinates if we have projection info
            if ($proj4 && $sourceProj && $targetProj && isset($geojson['coordinates'])) {
                $geojson['coordinates'] = $this->reprojectGeoJSONCoordinates(
                    $geojson['coordinates'], 
                    $sourceProj, 
                    $targetProj,
                    $proj4
                );
            }
            
            $features[] = [
                'geometry' => $geojson,
                'properties' => $attributes
            ];
            
            $count++;
        }
        
        Log::info("Successfully parsed {$count} features from shapefile");
        return $features;
        
    } catch (\Exception $e) {
        Log::error('Shapefile parsing error: ' . $e->getMessage());
        Log::warning('Falling back to sample data');
        return $this->getSampleFeatures();
    }
}

/**
 * Recursively reproject GeoJSON coordinates
 */
private function reprojectGeoJSONCoordinates($coords, $sourceProj, $targetProj, $proj4)
{
    if (is_array($coords) && isset($coords[0]) && is_numeric($coords[0])) {
        // This is a coordinate pair [x, y]
        try {
            // Create point with [x, y] (UTM easting, northing)
            $point = new Point($coords[0], $coords[1]);
            
            // Transform to WGS84
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
     * Recursively reproject coordinates
     */
    private function reprojectCoordinates($coords, $sourceProj, $targetProj, $proj4)
    {
        if (is_array($coords) && isset($coords[0]) && is_numeric($coords[0])) {
            // This is a coordinate pair [x, y]
            $point = $proj4->transform($sourceProj, $targetProj, [$coords[0], $coords[1]]);
            return [$point[0], $point[1]];
        } elseif (is_array($coords)) {
            // Recursively process nested arrays
            $result = [];
            foreach ($coords as $key => $value) {
                $result[$key] = $this->reprojectCoordinates($value, $sourceProj, $targetProj, $proj4);
            }
            return $result;
        }
        return $coords;
    }

    /**
     * Get sample features for testing
     */
    private function getSampleFeatures()
    {
        return [
            [
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [36.8219, -1.2921],
                        [36.8225, -1.2921],
                        [36.8225, -1.2915],
                        [36.8219, -1.2915],
                        [36.8219, -1.2921]
                    ]]
                ],
                'properties' => [
                    'land_use' => 'Residential',
                    'area' => 450,
                    'parcel_id' => 'R-001',
                    'description' => 'Sample residential area'
                ]
            ],
            [
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [36.8230, -1.2925],
                        [36.8240, -1.2925],
                        [36.8240, -1.2918],
                        [36.8230, -1.2918],
                        [36.8230, -1.2925]
                    ]]
                ],
                'properties' => [
                    'land_use' => 'Commercial',
                    'area' => 1200,
                    'parcel_id' => 'C-010',
                    'description' => 'Sample commercial area'
                ]
            ]
        ];
    }

    /**
     * Extract land use type from properties
     */
    private function extractLandUseType($properties)
    {
        // Common field names for land use type
        $possibleFields = [
            'land_use', 'landuse', 'lu', 'lu_code', 'lu_type',
            'type', 'types', 'category', 'cat', 'class', 'cls',
            'description', 'desc', 'name', 'label', 'use'
        ];
        
        // Check each possible field (case-insensitive)
        foreach ($possibleFields as $field) {
            foreach ($properties as $key => $value) {
                if (strtolower($key) === strtolower($field) && !empty($value)) {
                    return (string) $value;
                }
            }
        }
        
        return 'Unknown';
    }

    /**
     * Get human-readable error message for ZipArchive
     */
    private function getZipErrorMessage($errorCode)
    {
        $errors = [
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            ZipArchive::ER_RENAME => 'Rename error',
            ZipArchive::ER_CLOSE => 'Close error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Zip already closed',
            ZipArchive::ER_TMPOPEN => 'Failed to create temp file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_REMOVE => 'Can\'t remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted'
        ];
        
        return $errors[$errorCode] ?? 'Unknown error (code: ' . $errorCode . ')';
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Delete a specific land use record
     */
    public function deleteLandUse($id)
    {
        try {
            $landUse = LandUse::find($id);
            
            if (!$landUse) {
                return response()->json(['error' => 'Record not found'], 404);
            }
            
            $landUse->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting land use: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete record'], 500);
        }
    }

    /**
     * Get statistics about land use data
     */
    public function getStatistics()
    {
        try {
            $totalFeatures = LandUse::count();
            $landUseTypes = LandUse::select('land_use_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('land_use_type')
                ->get();
            
            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_features' => $totalFeatures,
                    'land_use_types' => $landUseTypes
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get statistics'], 500);
        }
    }

    /**
     * Spatial query example - Find features within distance
     */
    public function findWithinDistance(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'distance' => 'required|numeric|min:0'
        ]);

        try {
            // Use raw SQL for spatial query
            $features = DB::select("
                SELECT *, ST_AsGeoJSON(geometry) as geojson 
                FROM dm_land_uses 
                WHERE ST_DistanceSphere(geometry, ST_GeomFromText('POINT(? ?)', 4326)) <= ?
            ", [$request->lng, $request->lat, $request->distance]);
            
            return response()->json([
                'success' => true,
                'features' => $features,
                'count' => count($features)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in spatial query: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to query features'], 500);
        }
    }

    /**
 * Debug shapefile contents
 */
public function debugShapefile(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:zip'
    ]);

    try {
        // Store and extract zip
        $path = Storage::putFile('uploads/shapefiles', $request->file('file'));
        $zipPath = Storage::path($path);
        $extractPath = Storage::path('temp/debug_' . uniqid());
        
        mkdir($extractPath, 0777, true);
        
        $zip = new ZipArchive;
        $zip->open($zipPath);
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Find shapefile
        $shapefile = $this->findFileByExtension($extractPath, 'shp');
        
        if (!$shapefile) {
            return response()->json(['error' => 'No shapefile found'], 400);
        }
        
        $debug = [
            'shapefile_path' => $shapefile,
            'extracted_files' => scandir($extractPath),
            'parsing_results' => []
        ];
        
        // Try to parse with gasparesganga
        try {
            $shapefileReader = new \Shapefile\ShapefileReader($shapefile);
            
            $recordCount = 0;
            while ($record = $shapefileReader->fetchRecord()) {
                if ($record->isDeleted()) continue;
                
                $recordCount++;
                
                // Get raw data for debugging
                $geojson = $record->getGeoJSON();
                $geojsonArray = json_decode($geojson, true);
                $dataArray = $record->getDataArray();
                
                $debug['parsing_results'][] = [
                    'record_number' => $recordCount,
                    'geojson_raw' => $geojson,
                    'geojson_parsed' => $geojsonArray,
                    'data_array' => $dataArray,
                    'shape_type' => $record->getShapeType()
                ];
                
                // Only debug first 3 records
                if ($recordCount >= 3) break;
            }
            
            $shapefileReader->close();
            $debug['total_records'] = $recordCount;
            
        } catch (\Exception $e) {
            $debug['parser_error'] = $e->getMessage();
            $debug['parser_trace'] = $e->getTraceAsString();
        }
        
        // Cleanup
        $this->deleteDirectory($extractPath);
        Storage::delete($path);
        
        return response()->json($debug);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}