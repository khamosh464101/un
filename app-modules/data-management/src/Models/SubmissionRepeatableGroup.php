<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionRepeatableGroup extends Model
{
    protected $table = 'dm_submission_repeatable_groups';

    protected $fillable = [
        'group_name',
        'group_index',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(SubmissionRepeatableAttribute::class);
    }
}
