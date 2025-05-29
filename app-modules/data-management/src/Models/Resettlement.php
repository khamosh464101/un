<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resettlement extends Model
{
    protected $table = "dm_resettlements";
    protected $fillable = [
        'id',
        'relocate_another_place_by_government',
        'reason_notwantto_relocate',
        'relocate_minimum_condition',
        'relocate_another_place_by_government',
        'relocate_another_place_by_government',
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
