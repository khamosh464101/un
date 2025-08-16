<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandOwnershipDocument extends Model
{
    protected $table = "archive_dm_land_ownership_documents";
    protected $fillable = [
        'id',
        'house_document_photo',
        'dm_house_land_ownership_id',
    ];

    public bool $returnRawPhoto = false;
    public function getHouseDocumentPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->houseLandOwnership->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function houseLandOwnership(): BelongsTo
    {
        return $this->belongsTo(HouseLandOwnership::class, 'dm_house_land_ownership_id');
    }
}
