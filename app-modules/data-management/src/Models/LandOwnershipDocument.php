<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandOwnershipDocument extends Model
{
    protected $fillable = [
        'house_document_photo',
        'house_land_ownership_id',
    ];

    public function houseLandOwnership(): BelongsTo
    {
        return $this->belongsTo(HouseLandOwnership::class);
    }
}
