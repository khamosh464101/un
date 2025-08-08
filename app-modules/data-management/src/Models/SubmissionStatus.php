<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionStatus extends Model
{
        // use LogsActivity;
        protected $table = "dm_submission_statuses";
        protected $fillable = ['title', 'color', 'is_default'];

        // public function getActivitylogOptions(): LogOptions
        // {
        //     return LogOptions::defaults()
        //     ->logOnly(['title'])
        //     ->useLogName('Ticket Status')
        //     ->logOnlyDirty();
        //     // Chain fluent methods for configuration options
        // }
    
        public function submissions(): HasMany
        {
            return $this->hasMany(Submission::class);
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
                    $query = SubmissionStatus::where('id', '<>', $status->id)->where('is_default', true);
                    $query->update(['is_default' => false]);
                }
    
            });
            static::updated(function ($status) {
                if ($status->is_default) {
                    $query = SubmissionStatus::where('id', '<>', $status->id)->where('is_default', true);
                    $query->update(['is_default' => false]);
                }
    
            });
        }
}
