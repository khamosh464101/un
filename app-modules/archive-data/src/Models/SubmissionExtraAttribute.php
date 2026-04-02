<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionExtraAttribute extends Model
{
    protected $table = 'archive_dm_submission_extra_attributes';
    
    protected $fillable = [
        'id',
        'attribute_name',
        'attribute_value',
        'submission_id'

    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
