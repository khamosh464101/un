<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Auth;

class TicketPriority extends Model
{
    // use LogsActivity;

    protected $fillable = ['title', 'color', 'is_default'];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //     ->logOnly(['title'])
    //     ->useLogName('Ticket Priority')
    //     ->logOnlyDirty();
    //     // Chain fluent methods for configuration options
    // }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }

    public static function boot()
    {
        parent::boot();
        static::saved(function ($status) {
            if ($status->is_default) {
                $query = TicketPriority::where('id', '<>', $status->id)->where('is_default', true);
                $query->update(['is_default' => false]);
            }

        });
        static::updated(function ($status) {
            if ($status->is_default) {
                $query = TicketPriority::where('id', '<>', $status->id)->where('is_default', true);
                $query->update(['is_default' => false]);
            }

        });
    }
}
