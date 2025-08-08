<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentAssistance extends Model
{
    protected $table = "archive_dm_recent_assistances";
    protected $fillable = [
        'id',
        'receive_assistance',
        'type_assistance',
        'assistance_provided_by',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
