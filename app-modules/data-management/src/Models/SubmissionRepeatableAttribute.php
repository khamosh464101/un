<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionRepeatableAttribute extends Model
{
    protected $table = 'dm_submission_repeatable_attributes';

    protected $fillable = [
        'attribute_name',
        'attribute_value',
        'submission_repeatable_group_id',
    ];


    public function attributes(): BelongsTo
    {
        return $this->belongsTo(SubmissionRepeatableGroup::class);
    }
    
}
