<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Model;
use Auth;
class District extends Model
{
    // use LogsActivity;

    protected $fillable = ['name', 'is_urban', 'province_id'];
    
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //     ->logFillable()
    //     ->useLogName('Dsitrict')
    //     ->logOnlyDirty()
    //     ->setDescriptionForEvent(fn(string $eventName) => "This District has been {$eventName} by ". Auth::user()->name);
    //     // Chain fluent methods for configuration options
    // }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function gozars(): HasMany
    {
        return $this->hasMany(Gozar::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function subprojects(): BelongsToMany
    {
        return $this->belongsToMany(Subproject::class);
    }
}
