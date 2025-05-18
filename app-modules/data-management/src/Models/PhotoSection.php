<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoSection extends Model
{
    protected $table = "dm_photo_sections";
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


//     {
//     "photo_interviewee": "1724844532412.jpg",
//     "photo_house_building": "1724844540728.jpg",
//     "photo_house_door": "1724844563358.jpg",
//     "photo_enovirment": "1724844571435.jpg"
// }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
