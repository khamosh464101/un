<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillIdea extends Model
{
    protected $fillable = [
        'members_have_skills',
        'type_skills',
        'type_skills_other',
        'skills_want_learn',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
