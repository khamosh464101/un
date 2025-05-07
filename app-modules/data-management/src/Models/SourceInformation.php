<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceInformation extends Model
{
    protected $table = 'source_information';
    protected $fillable = [
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
        'village_name',
        'submission_id',
    ];

    public function submission (): belongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
