<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity as Actvty;
use Modules\DataManagement\Models\Submission;
use Storage;
use Auth;
use DB;

class Project extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 
        'start_date',
        'end_date',
        'code',
        'estimated_budget',
        'spent_budget',
        'logo', 
        'description', 
        'open_to_survey',
        'donor_id',
        'project_status_id',
        'google_storage_folder',
        'manager_id'

    ];

    protected $appends = ['created_at_formatted', 'updated_at_formatted'];

    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['project_status_id']);

        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Project')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Project has been {$eventName} by ". Auth::user()->name);

    }


    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Project')->orderBy('id', 'desc');
    }

    public function submissions(): BelongsToMany
    {
        return $this->belongsToMany(Submission::class, 'project_submission');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'project_id');
    }

    public function subprojects(): HasMany
    {
        return $this->hasMany(Subproject::class, 'project_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class);
    }

    public function gozars(): BelongsToMany
    {
        return $this->belongsToMany(Gozar::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class);
    }

    public function getLogoAttribute($value)
    {
        return $value ? asset("storage/$value") : null;
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

    public function statusActivities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class, 'project_id', 'id');
    }

    public function getProgress()
    {
        // Get all tickets through activities
        $tickets = $this->activities()->with('tickets')->get()
        ->pluck('tickets') // Get ticket collections
        ->flatten();       // Merge them into one collection

        if ($tickets->isEmpty()) {
            return 0; // Or null if you prefer
        }
        $totalProgress = $tickets->sum(function ($ticket) {
            return $ticket->progress_percent ?? 0;
        });


        $averageProgress = $totalProgress / $tickets->count();

        return round($averageProgress, 2);
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

    public function getUpdatedAtFormattedAttribute()
    {
       // Format the 'created_at' value
       $formattedDate = Carbon::parse($this->updated_at)->format('Y-m-d h:i A');

       // Get the human-readable relative time (e.g., "3 hours ago")
       $relativeTime = Carbon::parse($this->updated_at)->diffForHumans();

       // Return the formatted string
       return $formattedDate . ' (' . $relativeTime . ')';
    }


    public static function boot()
    {
        parent::boot();
        static::updating(function ($project) {
            if ($project->isDirty('logo') && !is_null($project->getRawOriginal('logo'))) {
                Storage::delete($project->getRawOriginal('logo'));
                
            }
            if ($project->isDirty('code')) {
                $project->activities()->update([
                    'activity_number' => DB::raw("CONCAT('{$project->code}', '-', id)")
                ]);
                // Activity::where('project_id', $project->id)
                // ->update([
                //     'activity_number' => DB::raw("CONCAT('{$project->code}', '-', id)")
                // ]);
            }

            if ($project->isDirty('project_status_id')) {
                $oldStatus = ProjectStatus::find($project->getOriginal('project_status_id'))->title ?? 'Unknown';
                $newStatus = ProjectStatus::find($project->project_status_id)->title ?? 'Unknown';
                activity()
                ->useLog('Project')
                ->causedBy(auth()->user()) // Log who made the change
                ->performedOn($project)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ])
                ->log("Project status changed from **$oldStatus** to **$newStatus** by " . auth()->user()->name);
            }

        });

        static::deleting(function ($project) {
            if (!is_null($project->getRawOriginal('logo'))) {
                Storage::delete($project->getRawOriginal('logo'));
            }

            $project->documents()->delete(); // Delete all related documents in one query
            $project->gozars()->detach();
            $project->staff()->detach();

        });
    }
}
