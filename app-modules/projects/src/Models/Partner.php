<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Auth;

class Partner extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_name',
        'address',
        'website',
        'description'
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Partner')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Partner has been {$eventName} by ". Auth::user()->name);
        // Chain fluent methods for configuration options
    }

    public function representative(): HasOne
    {
        return $this->hasOne(Representative::class, 'partner_id');
    }



}
