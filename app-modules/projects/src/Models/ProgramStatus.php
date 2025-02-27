<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class ProgramStatus extends Model
{
    use LogsActivity;

    protected $fillable = ['title'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['title'])
        ->useLogName('Program Status')
        ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
