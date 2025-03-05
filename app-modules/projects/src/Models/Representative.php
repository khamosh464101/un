<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Auth;

class Representative extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'phone1',
        'phone2',
        'email',
        'description',
        'partner_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Representative')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This record has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }


}
