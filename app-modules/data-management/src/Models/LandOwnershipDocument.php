<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

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
        return $value ? asset("storage/kobo-attachments/$value") : asset('import/assets/post-pic-dummy.png');
    }

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
