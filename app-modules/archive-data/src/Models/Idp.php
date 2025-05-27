<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Idp extends Model
{
    protected $table = "archive_dm_idps";
    protected $fillable = [
        'id',
        'year_idp',
        'idp_reason',
        'idp_securtiy_reason',
        'natural_disaster_reason',
        'other_reason',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
