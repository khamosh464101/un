<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodConsumptionScore extends Model
{
    protected $table = "archive_dm_food_consumption_scores";
    protected $fillable = [
        'id',
        'days_inweek_eaten_cereal',
        'days_inweek_eaten_pulse',
        'days_inweek_eaten_vegetables',
        'days_inweek_eaten_fruits',
        'days_inweek_eaten_animal',
        'days_inweek_eaten_dairy',
        'days_inweek_eaten_oil',
        'days_inweek_eaten_sugar',
        'days_inweek_eaten_bread',
        'food_cerel_source',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
