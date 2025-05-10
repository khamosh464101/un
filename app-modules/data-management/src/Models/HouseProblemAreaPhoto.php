<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseProblemAreaPhoto extends Model
{
    protected $fillable = [
        'current_house_problem_title',
        'current_house_problem_photo',
        'house_condition_id',
    ];

    public function houseCondition(): BelongsTo
    {
        return $this->belongsTo(HouseCondition::class);
    }
}
