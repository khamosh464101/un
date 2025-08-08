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
use Spatie\Activitylog\Models\Activity as Actvty;
use Auth;
use Storage;
use Carbon\Carbon;

class Activity extends Model
{
    use LogsActivity;
    protected $fillable = [
        "title",
        'activity_number',
        'starts_at',
        'ends_at',
        'description',
        'project_id',
        'activity_status_id',
        'activity_type_id',
    ];

   

    protected $appends = ['created_at_formatted'];
    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['activity_status_id']);

        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Activity')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Activity has been {$eventName} by ". Auth::user()->name);
      
        
        // Chain fluent methods for configuration options
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Activity')->orderBy('id', 'desc');
    }

    public function sprint(): HasOne
    {
        return $this->hasOne(ActivitySprint::class);
    }


    public function project (): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class);
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

    public function getProgress()
    {
        // Get all tickets through activities
        $tickets = $this->tickets()->get();
        if ($tickets->isEmpty()) {
            return 0; // Or null if you prefer
        }
        $totalProgress = $tickets->sum(function ($ticket) {
            return $ticket->progress_percent ?? 0;
        });
        $averageProgress = $totalProgress / $tickets->count();

        return round($averageProgress, 2);
    }

    public function getStartsAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getEndsAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }

    public function getCreatedAtFormattedAttribute()
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($this->created_at)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($this->created_at)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    public function getUpdatedAtAttribute($value)
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($value)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($value)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($activity) {
            $activity->activity_number = '#' . $activity->project->code . '-' . $activity->id;
            $activity->save();
        });

        static::updating(function ($activity) {
            if ($activity->isDirty('activity_status_id')) {
                $oldStatus = ActivityStatus::find($activity->getOriginal('activity_status_id'))->title ?? 'Unknown';
                $newStatus = ActivityStatus::find($activity->activity_status_id)->title ?? 'Unknown';
                activity()
                ->useLog('Activity')
                ->causedBy(auth()->user()) // Log who made the change
                ->performedOn($activity)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ])
                ->log("Ticket status changed from **$oldStatus** to **$newStatus** by " . auth()->user()->name);
            }
        });

        static::deleting(function ($activity) {
            $activity->documents()->delete(); // Delete all related documents in one query
            $activity->gozars()->detach();
        });

        
    }


}
