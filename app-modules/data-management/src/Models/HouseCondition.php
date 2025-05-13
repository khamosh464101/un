<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class HouseCondition extends Model
{
    protected $fillable = [
        'materials_house_constructed',
        'issues_current_house',
        'issues_current_house_other',
        'house_adequate_family_size',
        'house_adequate_family_size_no',
        'house_adequate_family_size_no_other',
        'made_housing_improvement',
        'made_housing_improvement_yes',
        'received_humanitarian_assistance',
        'received_humanitarian_assistance_type',
        'received_humanitarian_assistance_org',
        'shelter_support_received',
        'shelter_support_received_yes',
        'shelter_support_received_yes_other',
        'rate_need_shelter_repair',
        'surveyor_observation_current_house',
        'received_humanitarian_assistance_org',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function HouseProblemAreaPhoto(): HasMany
    {
        return $this->hasMany(HouseProblemAreaPhoto::class);
    }
}
