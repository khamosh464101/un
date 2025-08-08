<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceInformation extends Model
{
    
    protected $table = 'dm_source_information';
    protected $fillable = [
        'id',
        'survey_province',
        'district_name',
        'surveyors_code',
        'surveyors_name',
        'nahya_number',
        'kbl_guzar_number',
        'province_code',
        'city_name',
        'city_code',
        'district_code',
        'code_number',
        'block_number',
        'house_number',
        'area_representative_name',
        'area_representative_phone',
        'village_name',
        'submission_id',
    ];


    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public function submission (): belongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
