<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityActivity extends Model
{
    protected $fillable = [
        'project_id', 'old_status_id', 'new_status_id', 'user_id'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')->withTrashed();
    }

    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'old_status_id', 'id')->withTrashed();
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'new_status_id', 'id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityActivity::class, 'activity_id', 'id');
    }

    
    
}
