<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAvailability extends Model
{
    protected $table = "archive_dm_community_availabilties";
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function getCommunityCenterPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }
}
