<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DurableSolution extends Model
{
    protected $table = "dm_durable_solutions";
    protected $fillable = [
        'id',
        'future_families_preference',
        'local_integration_details',
        'local_integration_other',
        'do_you_have_land',
        'do_you_have_land_yes',
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
