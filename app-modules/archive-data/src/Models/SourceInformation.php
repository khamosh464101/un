<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceInformation extends Model
{
    
    protected $table = 'archive_dm_source_information';
    protected $fillable = [
        'id',
        'survey_province',
        'district_name',
        'surveyors_code',
        'surveyors_name',
        'nahya_number',
        'kbl_guzar_number',
        'block_number',
        'house_number',
        'area_representative_name',
        'area_representative_phone',
        'province_code',
        'city_name',
        'city_code',
        'district_code',
        'code_number',
        'village_name',
        'submission_id',
    ];
   
    

    public function submission (): belongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
