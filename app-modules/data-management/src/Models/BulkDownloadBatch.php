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
        
        if ($this->processed_items >= $this->total_items && $this->status !== 'completed') {
            $this->status = 'completed';
            $this->completed_at = now();
            $this->save();
            
            // 🔥 TRIGGER ZIP GENERATION HERE
            if ($this->successful_items > 0) {
                GenerateBatchZip::dispatch($this)->onQueue('bulk-downloads');
            }
        }

        
        $this->save();
    }
    public static function boot()
    {
        parent::boot();


        static::deleting(function ($batch) {

            Storage::disk('public')->deleteDirectory("bulk-downloads/batch-{$batch->batch_id}");

            if ($batch->zip_file_path && Storage::disk('public')->exists($batch->zip_file_path)) {
                Storage::disk('public')->delete($batch->zip_file_path);
            }

            $batch->items()->delete(); 
            $batch->logs()->delete();

        });
    }
}
