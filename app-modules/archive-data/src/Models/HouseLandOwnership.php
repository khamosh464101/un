<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseLandOwnership extends Model
{
    protected $table = "archive_dm_house_land_ownerships";
    protected $fillable = [
        'id',
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

    public bool $returnRawPhoto = false;

    public function getInterNicPhotoOwnerAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $tmpName = $this->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : null;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function landOwnershipDocument(): HasMany
    {
        return $this->hasMany(LandOwnershipDocument::class, 'dm_house_land_ownership_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($submission) {
            $submission->landOwnershipDocument()->delete(); // Delete all related documents in one query
        });
    }
}
