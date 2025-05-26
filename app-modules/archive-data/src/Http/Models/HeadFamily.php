<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeadFamily extends Model
{
    protected $table = "archive_dm_head_families";
    protected $fillable = [
        'hoh_name',
        'hoh_father_name',
        'hoh_grandfather_name',
        'hoh_phone_number',
        'does_hoh_have_nic',
        'hoh_nic_number',
        'hoh_nic_photo',
        'hoh_sex',
        'hoh_age',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }


}
