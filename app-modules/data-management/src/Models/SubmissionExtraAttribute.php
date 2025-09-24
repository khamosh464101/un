<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionExtraAttribute extends Model
{
    protected $table = 'dm_submission_extra_attributes';
    
    protected $fillable = [
        'attribute_name',
        'attribute_value',
        'submission_id'

    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
