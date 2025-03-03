<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Auth;

class Donor extends Model
{
    use LogsActivity;

    protected $fillable = ['name','description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Donor')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Donor has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }



    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'donor_id');
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
}
