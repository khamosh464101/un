<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FamilyInformation extends Model
{
    protected $table = "archive_dm_family_information";
    
    protected $fillable = [
        'id',
        'number_families',
        'household_size',
        'hoh_disable',
        'hof_or_interviewee',
        'hof_ethnicity',
        'province_origin',
        'district_origin',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    
}
