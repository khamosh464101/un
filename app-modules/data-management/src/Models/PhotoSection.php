<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Helpers\ImageFixer;
use App\Models\Setting;
use Log;

class PhotoSection extends Model
{
    protected $table = "dm_photo_sections";
    protected $fillable = [
        'id',
        'field_name',
        'latitude',
        'longitude',
        'altitude',
        'accuracy',
        'photo_interviewee',
        'photo_house_building',
        'photo_house_door',
        'photo_enovirment',
        'photo_other',
        'remarks',
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getPhotoInterviewweeAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getPhotoHouseBuildingAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : asset('images/default.png');
    }

    public function getPhotoHouseDoorAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getPhotoEnovirmentAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getPhotoOtherAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }


// public function getPhotoIntervieweeAttribute($value)
// {
//     if (!$value) {
//         return null;
//     }

//     $filename = $this->submission->_id . '-' . $value;
//     $originalPath = storage_path("app/public/kobo-attachments/$filename");
//     $rotatedPath = storage_path("app/public/tmp/rotated-$filename");

//     // Check if original file exists
//     if (!file_exists($originalPath)) {
//         return null;
//     }

//     // Create tmp directory if it doesn't exist
//     if (!file_exists(dirname($rotatedPath))) {
//         mkdir(dirname($rotatedPath), 0755, true);
//     }

//     // Only rotate and save once
//     if (!file_exists($rotatedPath)) {
//         $manager = new ImageManager(new Driver());
//         $image = $manager->read($originalPath);

//         // Try to read EXIF and rotate manually if needed
//         $exif = @exif_read_data($originalPath);
//         if (!empty($exif['Orientation'])) {
//             switch ($exif['Orientation']) {
//                 case 3:
//                     $image = $image->rotate(180);
//                     break;
//                 case 6:
//                     $image = $image->rotate(-90);
//                     break;
//                 case 8:
//                     $image = $image->rotate(90);
//                     break;
//             }
//         }

//         // Save rotated image
//         $image->save($rotatedPath);
//     }

//     return asset("storage/tmp/rotated-$filename");
// }


    // public function getPhotoIntervieweeAttribute($value)
    // {
    //     
        // return $value ? asset("storage/kobo-attachments/$value") : null;
    // }

    public function getPhotoIntervieweeAttribute($value)
    {
        // 1. Handle missing value
        if (!$value) {
            return null;
        }
        
        $originalPath = storage_path("app/public/kobo-attachments/$value");
        $publicStoragePath = "storage/kobo-attachments/$value"; // Path for asset()

        // 3. Check if original file exists
        if (!file_exists($originalPath)) {
            \Log::warning("Photo file not found at: " . $originalPath);
            return asset('images/default.png');
        }

        // 4. Try to fix image orientation.
        // IMPORTANT: Doing this on every accessor call is highly inefficient
        // especially for large images or many images on a page.
        // It's MUCH better to do this:
        //    a) When the image is first uploaded/saved.
        //    b) As a background job (Laravel Queues).
        // For demonstration, it's here as requested, but be aware of performance.

        // A simple way to avoid re-fixing: If the image is already processed once,
        // we might not need to run the heavy `fixFaceOrientationWithVision` again.
        // You could maintain a flag in the database (e.g., `photo_orientation_fixed`)
        // or check if a `_fixed` version of the file exists.
        // For simplicity, `fixFaceOrientationWithVision` itself checks.
        // ImageFixer::fixFaceOrientationWithVision($originalPath);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($originalPath);

        // logger()->info('Doorking', [$image->width()]);
        // if ($image->width() > $image->height()) {
        //     logger()->info('Still working', [$image->height()]);
        //     $image = $image->rotate(-90);
        // }
        // $image->save($originalPath);
        $this->fixOrientationWithObjectDetection($originalPath);
        // ImageFixer::fixFaceOrientationWithVision($originalPath);

        // 5. Return the asset path to the (potentially) fixed image.
        return asset($publicStoragePath);
    }

  
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    function fixFaceOrientationWithVision($localImagePath)
    {
        $imageAnnotator = new ImageAnnotatorClient();

        // Read image and send to Google Vision
        $imageData = file_get_contents($localImagePath);
        $response = $imageAnnotator->faceDetection($imageData);

        $faces = $response->getFaceAnnotations();

        if (count($faces) > 0) {
            $face = $faces[0]; // Use the first detected face
            $angle = $face->getRollAngle();

            // Only rotate if angle suggests upside-down (e.g., around 180°)
            if (abs($angle) > 135) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($localImagePath)->rotate(180);
                $image->save($localImagePath); // Overwrite original or save elsewhere
            }
        }

        $imageAnnotator->close();
    }
    public function fixOrientationWithObjectDetection(string $localImagePath)
{
    logger()->info('Starting object detection for orientation fix');
    
    $google_application_credentials = Setting::where('key', 'google_application_credentials')->first()->value;
    $google_cloud_project_id = Setting::where('key', 'google_cloud_project_id')->first()->value;
    
    if (!Storage::exists($google_application_credentials)) {
        Log::error("ImageFixer: Google credentials file not found at " . $google_application_credentials);
        return false;
    }

    try {
        $credentialsContent = Storage::get($google_application_credentials);
        $credentialsArray = json_decode($credentialsContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("ImageFixer: Invalid JSON in credentials file");
            return false;
        }

        $imageAnnotator = new ImageAnnotatorClient([
            'credentials' => $credentialsArray,
            'projectId' => $google_cloud_project_id,
        ]);

        logger()->info('Google Vision client initialized successfully');

        // Read image and send to Google Vision
        $imageData = file_get_contents($localImagePath);
        
        if ($imageData === false) {
            Log::error("ImageFixer: Could not read image file: " . $localImagePath);
            return false;
        }

        // Perform object localization - FIXED METHOD CALL
        $response = $imageAnnotator->objectLocalization($imageData);
        
        // Check if response has errors
        if ($response->getError()) {
            Log::error("Google Vision API Error: " . $response->getError()->getMessage());
            return false;
        }

        $personDetected = false;
        $personBoundingBox = null;
        $highestConfidence = 0;

        // Iterate through detected objects to find a 'Person'
        foreach ($response->getLocalizedObjectAnnotations() as $object) {
            if ($object->getName() === 'Person') {
                $confidence = $object->getScore();
                if ($confidence > 0.7 && $confidence > $highestConfidence) {
                    $personDetected = true;
                    $personBoundingBox = $object->getBoundingPoly();
                    $highestConfidence = $confidence;
                }
            }
        }

        if ($personDetected && $personBoundingBox) {
            logger()->info("Person detected with confidence: " . $highestConfidence);
            
            $vertices = $personBoundingBox->getVertices();
            
            if (count($vertices) < 4) {
                Log::error("Insufficient vertices in bounding box");
                return false;
            }

            // Get image dimensions for better orientation detection
            list($imageWidth, $imageHeight) = getimagesize($localImagePath);
            
            // Get the bounding box coordinates
            $minX = min(array_map(fn($v) => $v->getX(), $vertices));
            $maxX = max(array_map(fn($v) => $v->getX(), $vertices));
            $minY = min(array_map(fn($v) => $v->getY(), $vertices));
            $maxY = max(array_map(fn($v) => $v->getY(), $vertices));

            $boxWidth = $maxX - $minX;
            $boxHeight = $maxY - $minY;
            $boxAspectRatio = $boxWidth / max($boxHeight, 1); // Avoid division by zero

            // Improved orientation detection logic
            $inferredRotationDegrees = 0;

            // Check if person is likely sideways (90 or 270 degrees)
            if ($boxAspectRatio > 1.2) {
                // Person is wider than tall - likely sideways
                // Determine if it's 90 or 270 based on position in image
                $centerX = ($minX + $maxX) / 2;
                
                if ($centerX < $imageWidth / 3) {
                    $inferredRotationDegrees = 90; // Person on left side
                } elseif ($centerX > $imageWidth * 2/3) {
                    $inferredRotationDegrees = 270; // Person on right side
                } else {
                    // Can't determine, use text detection as fallback or skip
                    Log::info("Person detected but orientation ambiguous");
                    return false;
                }
            }
            // Check for upside down (180 degrees) - this is trickier
            elseif ($this->isPersonUpsideDown($vertices, $imageHeight)) {
                $inferredRotationDegrees = 180;
            }

            // Apply rotation if needed
            if ($inferredRotationDegrees !== 0) {
                try {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($localImagePath)->rotate($inferredRotationDegrees);
                    $image->save($localImagePath);
                    
                    Log::info("Image at {$localImagePath} rotated by {$inferredRotationDegrees} degrees based on object detection.");
                    return true;
                    
                } catch (\Exception $e) {
                    Log::error("Error rotating image: " . $e->getMessage());
                    return false;
                }
            } else {
                Log::info("No rotation needed based on object detection");
                return true;
            }

        } else {
            Log::info("No high-confidence 'Person' object detected in {$localImagePath}.");
            return false;
        }

    } catch (\Google\ApiCore\ApiException $e) {
        Log::error("Google API Exception: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        Log::error("Error processing image {$localImagePath}: " . $e->getMessage());
        return false;
    }
}

// Helper method to detect upside-down person (very basic heuristic)
private function isPersonUpsideDown($vertices, $imageHeight)
{
    // Simple heuristic: if the bottom of the bounding box is near the top of the image
    // and top is near the bottom, person might be upside down
    $minY = min(array_map(fn($v) => $v->getY(), $vertices));
    $maxY = max(array_map(fn($v) => $v->getY(), $vertices));
    
    // If the top of the person is in the bottom half and bottom is in the top half
    return ($minY > $imageHeight / 2 && $maxY < $imageHeight / 2);
}


     public static function boot()
    {
        parent::boot();

        static::deleting(function ($photoSection) {
            $photoInterviewee = $photoSection->getRawOriginal('photo_interviewee');
            $photoHouseBuilding = $photoSection->getRawOriginal('photo_house_building');
            $photoHouseDoor = $photoSection->getRawOriginal('photo_house_door');
            $photoEnovirment = $photoSection->getRawOriginal('photo_enovirment');
            $photoOther = $photoSection->getRawOriginal('photo_other');
            if (!is_null($photoInterviewee)) {
                Storage::delete("kobo-attachments/$photoInterviewee");
            }

            if (!is_null($photoHouseBuilding)) {
                Storage::delete("kobo-attachments/$photoHouseBuilding");
            }

            if (!is_null($photoHouseDoor)) {
                Storage::delete("kobo-attachments/$photoHouseDoor");
            }

            if (!is_null($photoEnovirment)) {
                Storage::delete("kobo-attachments/$photoEnovirment");
            }
            if (!is_null($photoOther)) {
                Storage::delete("kobo-attachments/$photoOther");
            }
        });
    }

}
