<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Storage;

class Document extends Model
{
    use LogsActivity;

    protected $fillable = ['title','path', 'description', 'size'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Document')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Document has been {$eventName} by ". Auth::user()->name);
      
        
        // Chain fluent methods for configuration options
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    // public function getPathAttribute($value)
    // {
    //     return $value ? asset("storage/$value") : null;
    // }

    public static function boot()
    {
        parent::boot();
        static::updating(function ($document) {
            if ($document->isDirty('path') && !is_null($document->getRawOriginal('path'))) {
                Storage::delete($document->getRawOriginal('path'));
            }

        });
        static::deleting(function ($document) {
            if (!is_null($document->getRawOriginal('path'))) {
                Storage::delete($document->getRawOriginal('path'));
            }

        });
    }
}
