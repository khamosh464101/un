<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Auth;
use Storage;

class Activity extends Model
{
    protected $fillable = [
        "title",
        'starts_at',
        'ends_at',
        'description',
        'project_id',
        'activity_status_id',
        'activity_type_id',
        'responsible_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Activity')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Activity has been {$eventName} by ". Auth::user()->name);
      
        
        // Chain fluent methods for configuration options
    }


    public function sprint(): HasOne
    {
        return $this->hasOne(ActivitySprint::class);
    }

    public function project (): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ActivityStatus::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function gozars(): BelongsToMany
    {
        return $this->belongsToMany(Gozars::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

}
