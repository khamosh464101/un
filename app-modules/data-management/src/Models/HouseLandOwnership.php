<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Storage;

class HouseLandOwnership extends Model
{
    protected $table = "dm_house_land_ownerships";
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

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getInterNicPhotoOwnerAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
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

        static::deleting(function ($houseLandOwnership) {
            $houseLandOwnership->landOwnershipDocument()->delete(); // Delete all related documents in one query
            $photo = $houseLandOwnership->getRawOriginal('inter_nic_photo_owner');
            if (!is_null($photo)) {
                $folderName = $houseLandOwnership->submission?->projects?->first()?->id;
                Storage::delete("kobo-attachments/$folderName/$photo");
            }
        });
    }
}
