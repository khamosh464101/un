<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity as Actvty;
use Auth;
use Carbon\Carbon;

class Partner extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_name',
        'address',
        'website',
        'representative_name',
        'representative_phone1',
        'representative_phone2',
        'representative_email',
        'description'
    ];

    protected $appends = ['created_at_formatted', 'updated_at_formatted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Partner')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Partner has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Partner')->orderBy('id', 'desc');
    }

    public function subprojects(): HasMany
    {
        return $this->hasMany(Subproject::class, 'partner_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
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

        static::deleting(function ($partner) {
            $partner->documents()->delete(); // Delete all related documents in one query

        });
    }



}
