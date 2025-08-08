<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Auth;
class Province extends Model
{
    // use LogsActivity;

    protected $fillable = ['name', 'name_fa', 'name_pa', 'latitude', 'longitude', 'code'];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //     ->logFillable()
    //     ->useLogName('Province')
    //     ->logOnlyDirty()
    //     ->setDescriptionForEvent(fn(string $eventName) => "This Province has been {$eventName} by ". Auth::user()->name);;
    //     // Chain fluent methods for configuration options
    // }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
