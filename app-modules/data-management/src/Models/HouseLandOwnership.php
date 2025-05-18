<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseLandOwnership extends Model
{
    protected $table = "dm_house_land_ownerships";
    protected $fillable = [
        'house_owner',
        'inter_name_owner',
        'inter_father_name_owner',
        'inter_phone_number_owner',
        'does_inter_have_nic_owner',
        'inter_nic_number_owner',
        'inter_nic_photo_owner',
        'type_tenure_document',
        'house_owner_myself',
        'house_document_number',
        'house_document_date',
        'duration_lived_thishouse',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function landOwnershipDocument(): HasMany
    {
        return $this->belongsTo(LandOwnershipDocument::class, 'dm_house_land_ownership_id');
    }
}
