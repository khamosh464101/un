<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;use Illuminate\Database\Eloquent\Casts\Attribute;

use Auth;
class Gozar extends Model
{
    // use LogsActivity;
    
    protected $fillable = ['name', 'district_id'];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //     ->logFillable()
    //     ->useLogName('Gozar')
    //     ->logOnlyDirty()
    //     ->setDescriptionForEvent(fn(string $eventName) => "This Gozar has been {$eventName} by ". Auth::user()->name);
    //     // Chain fluent methods for configuration options
    // }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class);
    }

    
}
