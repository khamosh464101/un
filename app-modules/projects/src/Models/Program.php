<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Storage;
use Auth;

class Program extends Model
{
    use LogsActivity;

    protected $fillable = ['title', 'logo', 'description', 'program_status_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Program')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Program has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Activity::class, 'subject_id')->where('log_name', 'Program')->orderBy('id', 'desc');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProgramActivity::class, 'program_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProgramStatus::class, 'program_status_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'program_id');
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


    public static function boot()
    {
        parent::boot();
        static::updating(function ($program) {
            if ($program->isDirty('logo') && !is_null($program->getRawOriginal('logo'))) {
                Storage::delete($program->getRawOriginal('logo'));
            }

        });
        static::deleting(function ($program) {
            if (!is_null($program->getRawOriginal('logo'))) {
                Storage::delete($program->getRawOriginal('logo'));
            }
            $program->documents()->delete(); // Delete all related documents in one query


        });
    }


}
