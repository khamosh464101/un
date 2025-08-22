<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class CommunityAvailability extends Model
{
    protected $table = "dm_community_availabilties";
    protected $fillable = [
        'id',
        'community_avalibility',
        'community_center_photo', 
        'community_org_female', 
        'community_org_male', 
        'Household_member_participate',  
        'Household_member_participate_yes',     
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public function getCommunityCenterPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

     public static function boot()
    {
         parent::boot();

        static::deleting(function ($communithAvailability) {
            $communityCenterPhoto = $communithAvailability->getRawOriginal('community_center_photo');
            if (!is_null($communityCenterPhoto)) {
                Storage::delete("kobo-attachments/$communityCenterPhoto");
            }
        });
    }


}
