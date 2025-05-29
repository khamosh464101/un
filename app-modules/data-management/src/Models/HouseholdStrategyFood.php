<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdStrategyFood extends Model
{
    protected $table = "dm_household_strategy_food";
    protected $fillable = [
        'id',
        'number_days_nothave_enough_food_less_expensive',
        'number_days_nothave_enough_food_barrow',
        'number_days_nothave_enough_food_limit_portion',
        'number_days_nothave_enough_food_restrict_sonsumption',
        'number_days_nothave_enough_food_reduce_meals',
        'household_stocks_cereals',
        'market_place',
        'marketplace_distance',    
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
