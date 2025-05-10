<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoSection extends Model
{
    protected $fillable = [
        'field_name',
        'latitude',
        'longitude',
        'altitude',
        'accuracy',
        'photo_interviewee',
        'photo_house_building',
        'photo_house_door',
        'photo_enovirment',
        'photo_other',
        'remarks',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
