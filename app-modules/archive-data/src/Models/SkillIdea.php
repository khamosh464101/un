<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillIdea extends Model
{
    protected $table = "archive_dm_skill_ideas";
    protected $fillable = [
        'id',
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
