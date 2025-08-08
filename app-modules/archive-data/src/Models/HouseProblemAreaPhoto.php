<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseProblemAreaPhoto extends Model
{
    protected $table = "archive_dm_house_problem_area_photos";
    protected $fillable = [
        'id',
        'current_house_problem_title',
        'current_house_problem_photo',
        'dm_house_condition_id',
    ];
    public bool $returnRawPhoto = false;
    public function getCurrentHouseProblemPhotoAttribute($value)
    {
        if ($returnRawPhoto) {
            return $value;
        }
        $tmpName = $this->houseCondition->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : null;
    }

    public function houseCondition(): BelongsTo
    {
        return $this->belongsTo(HouseCondition::class, 'dm_house_condition_id');
    }
}
