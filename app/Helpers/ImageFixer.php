<?php

namespace App\Helpers;

use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Setting;
use Exception;
use Storage;

class ImageFixer
{
    public static function fixFaceOrientationWithVision(string $imagePath, int $maxRecursions = 3, int $currentRecursion = 0): bool
    {
        $google_application_credentials = Setting::where('key', 'google_application_credentials')->first()->value;
        $google_cloud_project_id = Setting::where('key', 'google_cloud_project_id')->first()->value;
        if (!File::exists($imagePath)) {
            Log::warning("ImageFixer: File does not exist at path: " . $imagePath);
            return false;
        }

        // Check if the file is actually an image and can be read
        try {
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo === false) {
                Log::warning("ImageFixer: File is not a valid image or cannot be read: " . $imagePath);
                return false;
            }
            
            // Check if the image is one of the supported types
            $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP];
            if (!in_array($imageInfo[2], $supportedTypes)) {
                Log::warning("ImageFixer: Unsupported image type: " . $imagePath);
                return false;
            }
        } catch (Exception $e) {
            Log::warning("ImageFixer: Error checking image: " . $e->getMessage());
            return false;
        }

        try {
            if (!Storage::exists("$google_application_credentials")) {
                Log::error("ImageFixer: Google credentials file not found at " . "storage/$google_application_credentials");
                return false;
            }

            $vision = new ImageAnnotatorClient([
                'credentials' => json_decode(Storage::get("$google_application_credentials"), true),
                'projectId' => $google_cloud_project_id,
            ]);
        } catch (Exception $e) {
            Log::error("ImageFixer: Failed to initialize Vision API client: " . $e->getMessage());
            return false;
        }

        try {
            // Initialize Intervention Image first to check if the image can be loaded
            $manager = new ImageManager(new Driver());
            $img = $manager->read($imagePath);
            
            // Correct EXIF orientation first (before sending to Vision API)
            $img = self::correctExifOrientation($img, $imagePath);
            
            // Save the corrected image temporarily to send to Vision API
            $tempPath = tempnam(sys_get_temp_dir(), 'imgfix');
            $img->save($tempPath, 90);
            
            $content = file_get_contents($tempPath);
            if ($content === false) {
                Log::error("ImageFixer: Could not read image content from temp file");
                $vision->close();
                @unlink($tempPath);
                return false;
            }

            $image = (new Image())->setContent($content);
            $feature = (new Feature())
                ->setType(Feature\Type::FACE_DETECTION)
                ->setMaxResults(10); // Increase max results for better face detection

            $request = (new AnnotateImageRequest())
                ->setImage($image)
                ->setFeatures([$feature]);
            
            $batchRequest = (new BatchAnnotateImagesRequest())->setRequests([$request]);

            $batchResponse = $vision->batchAnnotateImages($batchRequest);
            $responses = $batchResponse->getResponses();

            if (empty($responses)) {
                Log::info("ImageFixer: No response from Vision API.");
                $vision->close();
                @unlink($tempPath);
                return true;
            }

            $response = $responses[0];
            if ($response->getError()) {
                Log::error("ImageFixer: Vision API error: " . $response->getError()->getMessage());
                $vision->close();
                @unlink($tempPath);
                return false;
            }

            $faces = $response->getFaceAnnotations();
            if (empty($faces)) {
                Log::info("ImageFixer: No faces detected in image.");
                $vision->close();
                @unlink($tempPath);
                return true;
            }

            // Get the most prominent face (largest bounding box with highest detection confidence)
            $largestFace = null;
            $largestArea = 0;
            $highestConfidence = 0;
            
            foreach ($faces as $face) {
                $vertices = $face->getBoundingPoly()->getVertices();
                if (count($vertices) >= 4) {
                    $width = abs($vertices[2]->getX() - $vertices[0]->getX());
                    $height = abs($vertices[2]->getY() - $vertices[0]->getY());
                    $area = $width * $height;
                    $confidence = $face->getDetectionConfidence();
                    
                    // Prioritize both size and confidence
                    if ($area > $largestArea || ($area == $largestArea && $confidence > $highestConfidence)) {
                        $largestArea = $area;
                        $highestConfidence = $confidence;
                        $largestFace = $face;
                    }
                }
            }

            if (!$largestFace) {
                Log::info("ImageFixer: Could not determine face bounding box.");
                $img->rotate(90);
                $img->save($imagePath, 100);
                $vision->close();
                @unlink($tempPath);
                return true;
            }

            $rollAngle = $largestFace->getRollAngle();
            $rotationAngle = self::calculateRotationAngle($rollAngle);
    
            if ($rotationAngle !== 0) {
                Log::info("ImageFixer: Detected face roll angle: " . round($rollAngle, 2) . 
                         " degrees. Rotating image by $rotationAngle degrees (attempt $currentRecursion/$maxRecursions).");
                
                $img->rotate($rotationAngle);
                $img->save($imagePath, 100);
                
                // Check if we need further correction
                if ($currentRecursion < $maxRecursions) {
                    // Recursive call with incremented counter
                    return self::fixFaceOrientationWithVision($imagePath, $maxRecursions, $currentRecursion + 1);
                } else {
                    Log::warning("ImageFixer: Max recursion depth ($maxRecursions) reached.");
                }
            } else {
                Log::info("ImageFixer: Face appears upright after $currentRecursion corrections.");
            }

            $vision->close();
            @unlink($tempPath);
            return true;

        } catch (Exception $e) {
            Log::error("ImageFixer: Error processing image " . basename($imagePath) . ": " . $e->getMessage());
            if (isset($vision)) {
                $vision->close();
            }
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            return false;
        }
    }

    protected static function calculateRotationAngle(float $rollAngle): int
    {
        $tolerance = 15; // degrees
        
        // Normalize the angle to be between -180 and 180
        $normalizedAngle = fmod($rollAngle, 360);
        if ($normalizedAngle > 180) {
            $normalizedAngle -= 360;
        } elseif ($normalizedAngle < -180) {
            $normalizedAngle += 360;
        }

        // Determine the required rotation
        if (abs($normalizedAngle) > (180 - $tolerance)) {
            return 180;
        } elseif ($normalizedAngle > (90 - $tolerance) && $normalizedAngle < (90 + $tolerance)) {
            return 270;
        } elseif ($normalizedAngle < (-90 + $tolerance) && $normalizedAngle > (-90 - $tolerance)) {
            return 90;
        }
        
        return 0;
    }

    protected static function correctExifOrientation(\Intervention\Image\Image $img, string $imagePath): \Intervention\Image\Image
    {
        if (!function_exists('exif_read_data')) {
            Log::warning("ImageFixer: EXIF functions not available. Cannot correct EXIF orientation.");
            return $img;
        }

        try {
            $type = exif_imagetype($imagePath);
            if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM])) {
                return $img;
            }

            $exif = @exif_read_data($imagePath);
            if (empty($exif['Orientation']) || $exif['Orientation'] == 1) {
                return $img;
            }

            Log::info("ImageFixer: Correcting EXIF orientation: " . $exif['Orientation']);

            switch ($exif['Orientation']) {
                case 2:
                    $img->flip('h');
                    break;
                case 3:
                    $img->rotate(180);
                    break;
                case 4:
                    $img->rotate(180)->flip('h');
                    break;
                case 5:
                    $img->rotate(270)->flip('h');
                    break;
                case 6:
                    $img->rotate(270);
                    break;
                case 7:
                    $img->rotate(90)->flip('h');
                    break;
                case 8:
                    $img->rotate(90);
                    break;
            }

            // Try to remove EXIF data to prevent double-processing
            // try {
            //     $img->strip();
            // } catch (Exception $e) {
            //     Log::warning("ImageFixer: Could not strip EXIF data: " . $e->getMessage());
            // }

        } catch (Exception $e) {
            Log::warning("ImageFixer: Error reading EXIF data: " . $e->getMessage());
        }

        return $img;
    }
}