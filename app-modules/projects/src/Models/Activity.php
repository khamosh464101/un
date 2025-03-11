<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Auth;
use Storage;
use Carbon\Carbon;

class Activity extends Model
{
    protected $fillable = [
        "title",
        'activity_number',
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

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_id');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(ActivityStatus::class, 'activity_status_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function gozars(): BelongsToMany
    {
        return $this->belongsToMany(Gozar::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Gozar::class);
    }

    public function getStartsAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getEndsAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($activity) {
            $activity->activity_number = '#' . $activity->project->code . '-' . $activity->id;
            $activity->save();
        });

        static::deleting(function ($activity) {
            $activity->documents()->delete(); // Delete all related documents in one query
        });
    }

}
