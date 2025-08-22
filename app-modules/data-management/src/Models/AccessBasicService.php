<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class AccessBasicService extends Model
{
    protected $table = "dm_access_basic_services";
    protected $fillable = [
        'id',
        'drinkingwater_main_source',
        'type_water_source',
        'water_source_distance',
        'water_source_route_safe',
        'water_source_route_safe_no',
        'water_collect_person',
        'water_quality',
        'water_point_photo',
        'type_toilet_facilities',
        'access_sanitation_photo',
        
        'access_education',
        'access_school',
        'type_school',
        'nearest_school',
        'access_school_university',
        'access_school_madrasa',
        'Household_members_attend_school_present',
        'members_attend_school_no',
        'members_attend_school_yes_boys',
        'members_attend_school_yes_girls',
        'Household_members_attend_madrasa_present_howmany',
        'members_attend_madrasa_no',
        'members_attend_madrasa_yes_boys',
        'members_attend_madrasa_yes_girls',
        'Household_members_attend_university_present',
        'litrate_Household_member',
        'number_male_child_Household',
        'number_female_child_Household',
        'access_education_photo',
        'access_education_no',

        'access_health_services',
        'health_facilities_type',
        'health_service_distance',
        'health_service_distance_no',
        'health_facility_have_female_staff',
        'health_challanges',
        'health_challanges_other',
        'access_health_photo',

        'type_access_road',
        'access_road_photo',

        'how_access_electricity',
        'energy_cooking',
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getWaterPointPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getAccessSanitationPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getAccessEducationPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getAccessHealthPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function getAccessRoadPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }


    public static function boot()
    {
        parent::boot();

        static::deleting(function ($accessBasicService) {
            $waterPointPhoto = $accessBasicService->getRawOriginal('water_point_photo');
            $accessSanitationPhoto = $accessBasicService->getRawOriginal('access_sanitation_photo');
            $accessEducationPhoto = $accessBasicService->getRawOriginal('access_education_photo');
            $accessHealthPhoto = $accessBasicService->getRawOriginal('access_health_photo');
            $accessRoadPhoto = $accessBasicService->getRawOriginal('access_road_photo');
            if (!is_null($waterPointPhoto)) {
                Storage::delete("kobo-attachments/$waterPointPhoto");
            }

            if (!is_null($accessSanitationPhoto)) {
                Storage::delete("kobo-attachments/$accessSanitationPhoto");
            }

            if (!is_null($accessEducationPhoto)) {
                Storage::delete("kobo-attachments/$accessEducationPhoto");
            }

            if (!is_null($accessHealthPhoto)) {
                Storage::delete("kobo-attachments/$accessHealthPhoto");
            }

            if (!is_null($accessRoadPhoto)) {
                Storage::delete("kobo-attachments/$accessRoadPhoto");
            }
        });
    }

}
