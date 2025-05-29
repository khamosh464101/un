<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Composition extends Model
{
    protected $table = "dm_compositions";
    protected $fillable = [
        'id',
        'female_0_1',
        'male_0_1',
        'female_1_5',
        'male_1_5',
        'female_6_12',
        'male_6_12',
        'female_13_17',
        'male_13_17',
        'female_18_30',
        'male_18_30',
        'female_30_60',
        'male_30_60',
        'female_60_above',
        'male_60_above',
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
