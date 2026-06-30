<?php

namespace Modules\DataManagement\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DataManagement\Jobs\GenerateBatchZip;
use Storage;

class BulkDownloadBatch extends Model
{
    protected $table = 'dm_bulk_download_batches';
    
    protected $fillable = [
        'batch_id', 'name', 'total_items', 'processed_items', 
        'successful_items', 'failed_items', 'status', 
        'zip_file_path', 'metadata', 'started_at', 'completed_at'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    public function items(): HasMany
    {
        return $this->hasMany(BulkDownloadItem::class, 'batch_id', 'batch_id');
    }
    
    public function logs(): HasMany
    {
        return $this->hasMany(BulkDownloadLog::class, 'batch_id', 'batch_id');
    }
    
    public function updateCounters(): void
    {
        $this->processed_items = $this->items()->whereIn('status', ['completed', 'failed'])->count();
        $this->successful_items = $this->items()->where('status', 'completed')->count();
        $this->failed_items = $this->items()->where('status', 'failed')->count();
        
        if ($this->processed_items >= $this->total_items && !in_array($this->status, ['completed', 'generating_zip'])) {
            $this->completed_at = now();
            
            if ($this->successful_items > 0) {
                // Set to generating_zip immediately — so frontend knows ZIP is not ready yet
                $this->status = 'generating_zip';
                $this->save();
                GenerateBatchZip::dispatch($this)->onQueue('bulk-downloads');
            } else {
                // No successful items — mark as completed (nothing to zip)
                $this->status = 'completed';
                $this->save();
            }
            return;
        }

        
        $this->save();
    }
    public static function boot()
    {
        parent::boot();


        static::deleting(function ($batch) {

            Storage::disk('gcs')->deleteDirectory("bulk-downloads/batch-{$batch->batch_id}");

            if ($batch->zip_file_path && Storage::disk('gcs')->exists($batch->zip_file_path)) {
                Storage::disk('gcs')->delete($batch->zip_file_path);
            }

            $batch->items()->delete(); 
            $batch->logs()->delete();

        });
    }
}
