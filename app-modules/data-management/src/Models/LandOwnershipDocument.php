<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandOwnershipDocument extends Model
{
    protected $table = "dm_land_ownership_documents";
    protected $fillable = [
        'house_document_photo',
        'dm_house_land_ownership_id',
    ];

    public function getHouseDocumentPhotoAttribute($value)
    {
        $tmpName = $value->houseLandOwnership->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : asset('import/assets/post-pic-dummy.png');
    }

    public function houseLandOwnership(): BelongsTo
    {
        return $this->belongsTo(HouseLandOwnership::class, 'dm_house_land_ownership_id');
    }
}
