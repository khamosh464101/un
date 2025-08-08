<?php

namespace Modules\Projects\Models;


use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity as Actvty;
use Carbon\Carbon;
use Auth;

class Subproject extends Model
{
    use LogsActivity;
    protected $fillable = [
        'title',
        'budget',
        'announcement_date',
        'date_of_contract',
        'number_of_months',
        'description',
        'partner_id',
        'project_id',
        'subproject_type_id'
    ];

    protected $appends = ['created_at_formatted', 'updated_at_formatted'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Subproject')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Subproject has been {$eventName} by ". Auth::user()->name);;
        // Chain fluent methods for configuration options
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Subproject')->orderBy('id', 'desc');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    public function type(): BelongsTo
    {
        return $this->belongsTo(SubprojectType::class, 'subproject_type_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function gozars(): BelongsToMany
    {
        return $this->belongsToMany(Gozar::class);
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class);
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

        static::deleting(function ($subproject) {
            $subproject->documents()->delete(); // Delete all related documents in one query
            $subproject->gozars()->detach();
            $subproject->districts()->detach();
        });
    }


}
