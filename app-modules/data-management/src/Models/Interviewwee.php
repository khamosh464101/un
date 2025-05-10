<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interviewwee extends Model
{
    protected $fillable = [
        'interviewee_hof_relation',
        'inter_name',
        'inter_father_name',
        'inter_grandfather_name',
        'inter_phone_number',
        'does_inter_have_nic',
        'inter_nic_number',
        'inter_nic_photo',
        'inter_sex',
        'inter_age',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }


}
