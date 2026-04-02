<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class ParcelImage extends Model
{
    protected $table = 'dm_parcel_images';
    
    protected $fillable = [
        'parcel_id',
        'image_path',
        'filename',
        'file_size',
        'status',
        'error_message',
        'retry_count',
        'processed_at'
    ];
    
    protected $casts = [
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
        'file_size' => 'integer'
    ];
    
    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }
    
    public function getUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($parcelImage) {
            $disk = Storage::disk('gcs');
            $filePath = $parcelImage->image_path ?? null;
            logger()->info('4321' . $filePath);


            if ($filePath && $disk->exists($filePath)) {
                logger()->info('432100000' . $filePath);
                $disk->delete($filePath);
            }
        });
    }
}