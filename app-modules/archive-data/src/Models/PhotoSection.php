<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Helpers\ImageFixer;
class PhotoSection extends Model
{
    protected $table = "archive_dm_photo_sections";
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

    public bool $returnRawPhoto = false;

    public function getPhotoInterviewweeAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function getPhotoHouseBuildingAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function getPhotoHouseDoorAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function getPhotoEnovirmentAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function getPhotoOtherAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }


//     {
//     "photo_interviewee": "1724844532412.jpg",
//     "photo_house_building": "1724844540728.jpg",
//     "photo_house_door": "1724844563358.jpg",
//     "photo_enovirment": "1724844571435.jpg"
// }

// "photo_interviewee": "1724844532412.jpg",
//         "photo_house_building": "1724844540728.jpg",
//         "photo_house_door": "1724844563358.jpg",
//         "photo_enovirment": "1724844571435.jpg",
//         "photo_other": null,

// public function getPhotoIntervieweeAttribute($value)
// {
//     if (!$value) {
//         returnnull;
//     }

//     $filename = $this->submission->_id . '-' . $value;
//     $originalPath = storage_path("app/public/kobo-attachments/$filename");
//     $rotatedPath = storage_path("app/public/tmp/rotated-$filename");

//     // Check if original file exists
//     if (!file_exists($originalPath)) {
//         returnnull;
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
    //    $folderName = $this->submission?->projects?->first()?->id;
        // return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    // }

     public function getPhotoIntervieweeAttribute($value)
    {
        // 1. Handle missing value
        if (!$value) {
            returnnull;
        }
         $folderName = $this->submission?->projects?->first()?->id;
        $originalPath = storage_path("app/public/kobo-attachments/$folderName/$value");
        $publicStoragePath = "storage/kobo-attachments/$folderName/$value"; // Path for asset()

        // 3. Check if original file exists
        if (!file_exists($originalPath)) {
            \Log::warning("Photo file not found at: " . $originalPath);
            returnnull;
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
        ImageFixer::fixFaceOrientationWithVision($originalPath);

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
}
