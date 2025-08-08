<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livelihood extends Model
{
    protected $table = "dm_livelihoods";
    protected $fillable = [
        'id',
        'Household_main_source_income',
        'women_engagement_income',
        'average_Household_monthly_income',
        'improve_livelihoods',
        'improve_livelihoods_other',
        'debt',
        'repaying_load_yes',
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
