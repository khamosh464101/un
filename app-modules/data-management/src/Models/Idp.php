<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Idp extends Model
{
    protected $table = "dm_idps";
    protected $fillable = [
        'id',
        'year_idp',
        'idp_reason',
        'idp_securtiy_reason',
        'natural_disaster_reason',
        'other_reason',
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
