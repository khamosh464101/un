<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionRepeatableAttribute extends Model
{
     protected $table = 'archive_dm_submission_repeatable_attributes';

    protected $fillable = [
        'id',
        'attribute_name',
        'attribute_value',
        'submission_repeatable_group_id',
    ];

    public function attributes(): BelongsTo
    {
        return $this->belongsTo(SubmissionRepeatableGroup::class);
    }
}
