<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity as Actvty;
use Storage;
use Auth;

class Project extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 
        'start_date',
        'end_date',
        'code',
        'budget',
        'logo', 
        'description', 
        'kobo_toolbox_id',
        'donor_id',
        'program_id',
        'project_status_id',
        'manager_id'

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Project')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Project has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Project')->orderBy('id', 'desc');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'project_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getLogoAttribute($value)
    {
        return $value ? asset("storage/$value") : asset('import/assets/post-pic-dummy.png');
    }
    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getStartDateAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getEndDateAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }


    public static function boot()
    {
        parent::boot();
        static::updating(function ($project) {
            if ($project->isDirty('logo') && !is_null($project->getRawOriginal('logo'))) {
                Storage::delete($project->getRawOriginal('logo'));
            }

        });
        static::deleting(function ($project) {
            if (!is_null($project->getRawOriginal('logo'))) {
                Storage::delete($project->getRawOriginal('logo'));
            }

        });
    }
}
