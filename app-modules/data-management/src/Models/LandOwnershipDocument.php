<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Helpers\ImageFixer;
use App\Helpers\AllImageFixer;
use Log;

class LandOwnershipDocument extends Model
{
    protected $table = "dm_land_ownership_documents";
    protected $fillable = [
        'id',
        'house_document_photo',
        'dm_house_land_ownership_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;
    public function getHouseDocumentPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        // 1. Handle missing value
        if (!$value) {
            return null;
        }
        $originalPath = storage_path("app/public/kobo-attachments/$value");
        $publicStoragePath = "storage/kobo-attachments/$value"; // Path for asset()

        if (!file_exists($originalPath)) {
            \Log::warning("Photo file not found at: " . $originalPath);
            return null;
        }
        $manager = new ImageManager(new Driver());
        $image = $manager->read($originalPath);
        $image->orient(); // v3 (or orientate() if using v2)
        $image->save($originalPath);
        return asset($publicStoragePath);
        return $value ? asset("storage/kobo-attachments/$value") : asset('images/default.png');
    }
    // public function getHouseDocumentPhotoAttribute($value)
    // {
    //     if ($this->returnRawPhoto) {
    //         return $value;
    //     }
    //     return $value ? asset("storage/kobo-attachments/$value") : null;
    //     $publicStoragePath = "storage/kobo-attachments/$value"; 
    //     if (!file_exists($originalPath)) {
    //         \Log::warning("Photo file not found at: " . $originalPath);
    //         return null;
    //     }


    //     AllImageFixer::fixImageOrientation($originalPath);
    //     return asset($publicStoragePath);
    // }

    public function houseLandOwnership(): BelongsTo
    {
        return $this->belongsTo(HouseLandOwnership::class, 'dm_house_land_ownership_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($landOwnershipDocument) {
            $houseDocumentPhoto = $landOwnershipDocument->getRawOriginal('house_document_photo');
            if (!is_null($houseDocumentPhoto)) {
                Storage::delete("kobo-attachments/$houseDocumentPhoto");
            }
        });
    }


}
