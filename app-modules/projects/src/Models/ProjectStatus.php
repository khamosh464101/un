<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use LogsActivity;

    protected $fillable = ['title'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['title'])
        ->useLogName('Project Status')
        ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
