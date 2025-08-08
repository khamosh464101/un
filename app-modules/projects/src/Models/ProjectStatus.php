<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;


use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use LogsActivity;

    protected $fillable = ['title', 'color', 'is_default'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['title'])
        ->useLogName('Project Status')
        ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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
                $query = ProjectStatus::where('id', '<>', $status->id)->where('is_default', true);
                $query->update(['is_default' => false]);
            }

        });
        static::updated(function ($status) {
            if ($status->is_default) {
                $query = ProjectStatus::where('id', '<>', $status->id)->where('is_default', true);
                $query->update(['is_default' => false]);
            }

        });
    }
}
