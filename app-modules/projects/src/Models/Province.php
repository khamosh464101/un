<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'name_fa', 'name_pa', 'latitude', 'longitude', 'code'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name'])
        ->useLogName('Province')
        ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
