<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAvailabilty extends Model
{
    protected $fillable = [
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
}
