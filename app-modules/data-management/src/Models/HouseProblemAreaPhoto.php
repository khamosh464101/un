<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseProblemAreaPhoto extends Model
{
    protected $table = "dm_house_problem_area_photos";
    protected $fillable = [
        'current_house_problem_title',
        'current_house_problem_photo',
        'dm_house_condition_id',
    ];

    public function getCurrentHouseProblemPhotoAttribute($value)
    {
        $tmpName = $value->houseCondition->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : asset('import/assets/post-pic-dummy.png');
    }

    public function houseCondition(): BelongsTo
    {
        return $this->belongsTo(HouseCondition::class, 'dm_house_condition_id');
    }
}
