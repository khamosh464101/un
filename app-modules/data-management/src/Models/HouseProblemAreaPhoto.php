<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class HouseProblemAreaPhoto extends Model
{
    protected $table = "dm_house_problem_area_photos";
    protected $fillable = [
        'id',
        'current_house_problem_title',
        'current_house_problem_photo',
        'dm_house_condition_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;
    public function getCurrentHouseProblemPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->houseCondition?->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function houseCondition(): BelongsTo
    {
        return $this->belongsTo(HouseCondition::class, 'dm_house_condition_id');
    }

     public static function boot()
    {
         parent::boot();

        static::deleting(function ($houseProblemAreaPhoto) {
            $photo = $houseProblemAreaPhoto->getRawOriginal('current_house_problem_photo');
            if (!is_null($photo)) {
                $folderName = $houseProblemAreaPhoto?->houseCondition?->submission?->projects?->first()?->id;
                Storage::delete("kobo-attachments/$folderName/$photo");
            }
        });
    }
    
}
