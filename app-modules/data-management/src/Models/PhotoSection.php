<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Helpers\ImageFixer;

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
     public function fixOrientationWithObjectDetection(string $localImagePath): void
    {
        $imageAnnotator = new ImageAnnotatorClient();

        try {
            // Read image and send to Google Vision
            $imageData = file_get_contents($localImagePath);

            // Perform object localization
            $response = $imageAnnotator->objectLocalization($imageData);

            $personDetected = false;
            $personBoundingBox = null;

            // Iterate through detected objects to find a 'Person'
            foreach ($response->getLocalizedObjectAnnotations() as $object) {
                if ($object->getName() === 'Person' && $object->getScore() > 0.7) { // Only consider high-confidence detections
                    $personDetected = true;
                    $personBoundingBox = $object->getBoundingPoly(); // Get the bounding polygon
                    break; // Use the first high-confidence person detected
                }
            }

            if ($personDetected && $personBoundingBox) {
                $vertices = $personBoundingBox->getVertices();

                // Get the bounding box coordinates (assuming 4 vertices for a rectangle)
                // Note: The order of vertices can vary, so calculate min/max to be safe
                $minX = min($vertices[0]->getX(), $vertices[1]->getX(), $vertices[2]->getX(), $vertices[3]->getX());
                $maxX = max($vertices[0]->getX(), $vertices[1]->getX(), $vertices[2]->getX(), $vertices[3]->getX());
                $minY = min($vertices[0]->getY(), $vertices[1]->getY(), $vertices[2]->getY(), $vertices[3]->getY());
                $maxY = max($vertices[0]->getY(), $vertices[1]->getY(), $vertices[2]->getY(), $vertices[3]->getY());

                $boxWidth = $maxX - $minX;
                $boxHeight = $maxY - $minY;

                // --- CUSTOM ORIENTATION INFERENCE LOGIC HERE ---
                // This is the challenging part. There's no direct "angle" for generic objects.
                // You'll need to develop a heuristic.
                // Examples of heuristics:
                // 1. Aspect Ratio: If a standing person's bounding box is significantly wider than it is tall,
                //    it might indicate a 90 or 270 degree rotation.
                //    e.g., if ($boxWidth > $boxHeight * 1.5) { /* potentially 90/270 deg rotation */ }
                // 2. Relative Position (for 180 degrees): This is very tricky without knowing head/feet position.
                //    If you also use another Vision API feature like 'Landmark detection' (though primarily for famous landmarks)
                //    or if you could somehow infer ground plane.
                //    For a pure 180-degree "upside down" of a person, the bounding box aspect ratio won't change,
                //    making it hard to detect from dimensions alone.
                //    This often requires a deeper understanding of human pose estimation, which is not
                //    a standard output of basic object localization.
                //    For text, `textDetection` gives block orientation, which is much more reliable for 180-deg.

                // Placeholder for inferred rotation angle (e.g., 0, 90, 180, 270)
                $inferredRotationDegrees = 0; // Default to no rotation

                // Example: Very basic heuristic for 90/270 degree rotation based on aspect ratio
                // If a "person" bounding box is landscape, it's likely a 90 or 270 deg rotation.
                if ($boxWidth > $boxHeight * 1.5) { // Adjust factor as needed
                    // This is a guess. You'd need more context (like image dimensions)
                    // and possibly other cues to differentiate 90 from 270,
                    // or to confirm it's not just a person lying down.
                    // For the sake of demonstration, let's assume it implies a 90-degree correction is needed,
                    // or that the image itself is landscape when it should be portrait.
                    // THIS IS HIGHLY SIMPLISTIC AND MIGHT NOT BE ACCURATE FOR 180-DEGREE UPSIDE DOWN.
                    // For true 180-degree detection (upside-down), text detection is often more reliable
                    // if there's any text in the image.
                    $inferredRotationDegrees = 180; // Assuming the original problem was about 180
                }
                // --- END CUSTOM ORIENTATION INFERENCE LOGIC ---


                // Apply rotation if needed
                // The threshold for rotation might need to be adjusted based on your heuristic
                if ($inferredRotationDegrees === 180) { // Or other angles you infer (90, 270)
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($localImagePath)->rotate(180);
                    $image->save($localImagePath); // Overwrite original or save elsewhere
                    // Log or return success message
                    error_log("Image at {$localImagePath} rotated by 180 degrees based on object detection.");
                }

            } else {
                error_log("No high-confidence 'Person' object detected in {$localImagePath}. Cannot infer orientation using this method.");
            }

        } catch (\Exception $e) {
            error_log("Error processing image {$localImagePath}: " . $e->getMessage());
        } finally {
            $imageAnnotator->close();
        }
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
