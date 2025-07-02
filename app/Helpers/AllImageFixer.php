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

class AllImageFixer
{
    public static function fixImageOrientation(string $imagePath, int $maxRecursions = 3, int $currentRecursion = 0): bool
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
            
            // Set up multiple detection features
            $features = [
                (new Feature())->setType(Feature\Type::FACE_DETECTION)->setMaxResults(10),
                (new Feature())->setType(Feature\Type::IMAGE_PROPERTIES),
                (new Feature())->setType(Feature\Type::TEXT_DETECTION),
                (new Feature())->setType(Feature\Type::OBJECT_LOCALIZATION)
            ];

            $request = (new AnnotateImageRequest())
                ->setImage($image)
                ->setFeatures($features);
            
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

            $rotationAngle = 0;
            
            // First try face detection
            $faces = $response->getFaceAnnotations();
            if (!empty($faces)) {
                $rotationAngle = self::getRotationFromFaces($faces);
            }
            
            // If no faces or couldn't determine from faces, try other methods
            if ($rotationAngle === 0) {
                // Try text detection
                $textAnnotations = $response->getTextAnnotations();
                if (!empty($textAnnotations)) {
                    $rotationAngle = self::getRotationFromText($textAnnotations);
                }
                
                // Try object detection
                if ($rotationAngle === 0) {
                    $objects = $response->getLocalizedObjectAnnotations();
                    if (!empty($objects)) {
                        $rotationAngle = self::getRotationFromObjects($objects);
                    }
                }
                
                // Try image properties (dominant colors)
                if ($rotationAngle === 0) {
                    $imageProperties = $response->getImagePropertiesAnnotation();
                    if ($imageProperties) {
                        $rotationAngle = self::getRotationFromImageProperties($imageProperties);
                    }
                }
            }

            if ($rotationAngle !== 0) {
                Log::info("ImageFixer: Detected rotation angle: " . $rotationAngle . 
                         " degrees (attempt $currentRecursion/$maxRecursions).");
                
                $img->rotate($rotationAngle);
                $img->save($imagePath, 100);
                
                // Check if we need further correction
                if ($currentRecursion < $maxRecursions) {
                    // Recursive call with incremented counter
                    return self::fixImageOrientation($imagePath, $maxRecursions, $currentRecursion + 1);
                } else {
                    Log::warning("ImageFixer: Max recursion depth ($maxRecursions) reached.");
                }
            } else {
                Log::info("ImageFixer: Image appears upright after $currentRecursion corrections.");
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

    protected static function getRotationFromFaces(\Google\Protobuf\Internal\RepeatedField $faces): int
{
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
            
            if ($area > $largestArea || ($area == $largestArea && $confidence > $highestConfidence)) {
                $largestArea = $area;
                $highestConfidence = $confidence;
                $largestFace = $face;
            }
        }
    }

    if (!$largestFace) {
        return 0;
    }

    $rollAngle = $largestFace->getRollAngle();
    return self::calculateRotationAngle($rollAngle);
}

protected static function getRotationFromText(\Google\Protobuf\Internal\RepeatedField $textAnnotations): int
{
    if ($textAnnotations->count() <= 1) {
        return 0; // Skip if no meaningful text blocks
    }

    $angles = [];
    $rtlDominanceThreshold = 0.6; // If >60% text is RTL, assume RTL document

    // Convert to array and skip first element (full text)
    $texts = iterator_to_array($textAnnotations);
    $totalTextBlocks = count($texts) - 1;
    $rtlTextBlocks = 0;

    foreach (array_slice($texts, 1) as $text) {
        $vertices = $text->getBoundingPoly()->getVertices();
        if (count($vertices) < 2) continue;

        // Detect RTL text (Persian/Arabic/Hebrew)
        $isRtl = self::isRtlText($text->getDescription());
        if ($isRtl) $rtlTextBlocks++;

        // Calculate angle based on text direction
        $xDiff = $vertices[1]->getX() - $vertices[0]->getX();
        $yDiff = $vertices[1]->getY() - $vertices[0]->getY();
        
        if ($xDiff != 0) {
            $angle = rad2deg(atan2($yDiff, $xDiff));
            
            // Adjust angle calculation for RTL text
            if ($isRtl) {
                $angle = fmod($angle + 180, 360); // Reverse direction
            }
            
            $angles[] = $angle;
        }
    }

    // If majority text is RTL, adjust angle interpretation
    $isRtlDominant = ($rtlTextBlocks / $totalTextBlocks) > $rtlDominanceThreshold;

    if (empty($angles)) return 0;

    // Get median angle
    sort($angles);
    $medianAngle = $angles[floor(count($angles) / 2)];

    // Adjusted rotation logic for RTL documents
    if ($isRtlDominant) {
        if ($medianAngle > -45 && $medianAngle < 45) return 0;
        if ($medianAngle >= 45 && $medianAngle < 135) return 270;
        if ($medianAngle >= 135 || $medianAngle <= -135) return 180;
        if ($medianAngle > -135 && $medianAngle <= -45) return 90;
    } else {
        // Original LTR logic
        if ($medianAngle > 45) return 90;
        if ($medianAngle < -45) return 270;
        if (abs($medianAngle) > 15) return 180;
    }

    return 0;
}

protected static function isRtlText(string $text): bool
{
    // Check for RTL characters (Arabic, Persian, Hebrew, etc.)
    $rtlRanges = [
        [0x0590, 0x05FF], // Hebrew
        [0x0600, 0x06FF], // Arabic
        [0x0750, 0x077F], // Arabic Supplement
        [0x08A0, 0x08FF], // Arabic Extended-A
        [0xFB50, 0xFDFF], // Arabic Presentation Forms-A
        [0xFE70, 0xFEFF], // Arabic Presentation Forms-B
        [0x10E60, 0x10E7F], // Rumi Numeral Symbols
    ];

    foreach (mb_str_split($text) as $char) {
        $code = mb_ord($char);
        foreach ($rtlRanges as $range) {
            if ($code >= $range[0] && $code <= $range[1]) {
                return true;
            }
        }
    }
    return false;
}

protected static function getRotationFromObjects(\Google\Protobuf\Internal\RepeatedField $objects): int
{
    logger()->info('getRotationFromObjects is working');
    $angles = [];
    $confidences = [];
    
    foreach ($objects as $object) {
        $vertices = $object->getBoundingPoly()->getNormalizedVertices();
        if (count($vertices) >= 2) {
            // Calculate angle based on the first edge
            $xDiff = $vertices[1]->getX() - $vertices[0]->getX();
            $yDiff = $vertices[1]->getY() - $vertices[0]->getY();
            
            if ($xDiff != 0) {
                $angle = rad2deg(atan($yDiff / $xDiff));
                $angles[] = $angle;
                $confidences[] = $object->getScore();
            }
        }
    }

    if (empty($angles)) {
        return 0;
    }

    // Weight angles by confidence
    $weightedAngles = [];
    foreach ($angles as $i => $angle) {
        $weight = $confidences[$i];
        $weightedAngles = array_merge($weightedAngles, array_fill(0, $weight * 100, $angle));
    }

    if (empty($weightedAngles)) {
        return 0;
    }

    // Get the median weighted angle
    sort($weightedAngles);
    $medianAngle = $weightedAngles[floor(count($weightedAngles) / 2)];
    
    // Determine the closest cardinal rotation
    if ($medianAngle > 45) {
        return 90;
    } elseif ($medianAngle < -45) {
        return 270;
    } elseif (abs($medianAngle) > 15) {
        return 180;
    }

    return 0;
}

protected static function getRotationFromImageProperties($imageProperties): int
{
    logger()->info('getRotationFromImageProperties is working');
    $dominantColors = $imageProperties->getDominantColors()->getColors();
    
    if ($dominantColors->count() === 0) {
        return 0;
    }

    // Convert RepeatedField to array for sorting
    $colorsArray = iterator_to_array($dominantColors);
    
    // Sort by score in descending order
    usort($colorsArray, function($a, $b) {
        return $b->getScore() <=> $a->getScore();
    });
    
    $topColor = $colorsArray[0]->getColor();
    $topPixelFraction = $colorsArray[0]->getPixelFraction();
    
    // If the top color covers most of the image, it might be a background
    // and we can't determine orientation from it
    if ($topPixelFraction > 0.8) {
        return 0;
    }

    // Try to find a color that's strongly positioned in one quadrant
    $quadrants = [0, 0, 0, 0]; // TL, TR, BL, BR
    
    foreach ($colorsArray as $colorInfo) {
        $color = $colorInfo->getColor();
        $score = $colorInfo->getScore();
        $pixelFraction = $colorInfo->getPixelFraction();
        
        // Skip very small or very large color areas
        if ($pixelFraction < 0.05 || $pixelFraction > 0.5) {
            continue;
        }
        
        // Get the position (simplified - in reality would need more analysis)
        $pos = $colorInfo->getPixelFraction(); // This is a simplification
        
        // Increment quadrant counters based on color position
        // This is a heuristic approach - real implementation would need actual position data
        if ($pos < 0.25) $quadrants[0]++;
        elseif ($pos < 0.5) $quadrants[1]++;
        elseif ($pos < 0.75) $quadrants[2]++;
        else $quadrants[3]++;
    }

    // Heuristic: If one quadrant is significantly different, try rotating
    $maxQuadrant = max($quadrants);
    $minQuadrant = min($quadrants);
    
    if ($maxQuadrant > 2 * $minQuadrant) {
        $quadrantIndex = array_search($maxQuadrant, $quadrants);
        // Try rotating based on which quadrant is most dominant
        switch ($quadrantIndex) {
            case 1: return 90;  // Top-right dominant - rotate 90
            case 2: return 180; // Bottom-left dominant - rotate 180
            case 3: return 270; // Bottom-right dominant - rotate 270
        }
    }

    return 0;
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

        } catch (Exception $e) {
            Log::warning("ImageFixer: Error reading EXIF data: " . $e->getMessage());
        }

        return $img;
    }
}