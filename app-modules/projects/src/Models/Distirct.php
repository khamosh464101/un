<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class Distirct extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'name_fa', 'name_pa', 'latitude', 'longitude', 'code', 'province_id'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Dsitrict')
        ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
