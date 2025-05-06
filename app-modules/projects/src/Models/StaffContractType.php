<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Auth;

class StaffContractType extends Model
{



        protected $fillable = ['title', 'color', 'is_default'];

    
        public function staff(): HasMany
        {
            return $this->hasMany(Staff::class);
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
            static::saved(function ($type) {
                if ($type->is_default) {
                    $query = StaffContractType::where('id', '<>', $type->id)->where('is_default', true);
                    $query->update(['is_default' => false]);
                }
    
            });
            static::updated(function ($type) {
                if ($type->is_default) {
                    $query = StaffContractType::where('id', '<>', $type->id)->where('is_default', true);
                    $query->update(['is_default' => false]);
                }
    
            });
        }
}
