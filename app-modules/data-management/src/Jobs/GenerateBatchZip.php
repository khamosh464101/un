<?php

namespace Modules\DataManagement\Jobs;

use Modules\DataManagement\Models\BulkDownloadBatch;
use Modules\DataManagement\Models\BulkDownloadItem;
use Modules\DataManagement\Models\BulkDownloadLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Exception;

class GenerateBatchZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600; // 1 hour timeout for large files
    public $tries = 3;
    public $backoff = [60, 300, 600]; // Retry after 1 min, 5 min, 10 min
    
    protected $batch;
    
    public function __construct(BulkDownloadBatch $batch)
    {
        $this->batch = $batch;
        $this->onQueue('bulk-downloads'); // Specify the queue
    }
    
    public function handle()
    {
        try {
            // Update status to processing
            $this->batch->update(['status' => 'generating_zip']);
            
            $this->log('info', 'Started generating ZIP file');
            
            $completedFiles = BulkDownloadItem::where('batch_id', $this->batch->batch_id)
                ->where('status', 'completed')
                ->whereNotNull('file_path')
                ->get();
                
            if ($completedFiles->isEmpty()) {
                $this->log('warning', 'No completed files found for ZIP generation');
                $this->batch->update(['status' => 'completed']);
                return;
            }
            
            // Create temp directory if not exists
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0775, true);
            }
            
            $zipFileName = Str::slug($this->batch->name) . '-' . $this->batch->batch_id . '.zip';
            $zipPath = $tempDir . '/' . $zipFileName;
            
            $this->log('info', 'Creating ZIP file', ['zip_path' => $zipPath]);
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Could not create ZIP file");
            }
            
            $fileCount = 0;
            foreach ($completedFiles as $file) {
                $fullPath = Storage::disk('public')->path($file->file_path);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, $file->file_name ?? basename($file->file_path));
                    $fileCount++;
                } else {
                    $this->log('warning', 'File not found', ['file_path' => $file->file_path]);
                }
            }
            
            $zip->close();
            
            $this->log('info', 'ZIP file created', ['file_count' => $fileCount, 'size' => filesize($zipPath)]);
            
            // Create permanent storage directory
            $zipStorageDir = 'bulk-downloads/zips';
            if (!Storage::disk('public')->exists($zipStorageDir)) {
                Storage::disk('public')->makeDirectory($zipStorageDir);
            }
            
            // Move to permanent storage
            $zipStoragePath = $zipStorageDir . '/' . $zipFileName;
            Storage::disk('public')->put($zipStoragePath, file_get_contents($zipPath));
            
            // Update batch with ZIP path
            $this->batch->update([
                'zip_file_path' => $zipStoragePath,
                'status' => 'completed'
            ]);
            
            // Clean up temp file
            @unlink($zipPath);
            
            $this->log('info', 'ZIP generation completed', ['zip_path' => $zipStoragePath]);
            
        } catch (Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }
    
    private function handleFailure(Exception $e)
    {
        $this->batch->update([
            'status' => 'failed',
            'metadata' => array_merge($this->batch->metadata ?? [], [
                'zip_error' => $e->getMessage(),
                'zip_error_at' => now()->toIso8601String()
            ])
        ]);
        
        $this->log('error', 'Failed to generate ZIP', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    private function log($level, $message, $context = [])
    {
        try {
            BulkDownloadLog::create([
                'batch_id' => $this->batch->batch_id,
                'level' => $level,
                'message' => $message,
                'context' => $context
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to create bulk download log', [
                'error' => $e->getMessage(),
                'batch_id' => $this->batch->batch_id
            ]);
        }
    }
    
    public function failed(Exception $e)
    {
        $this->batch->update([
            'status' => 'failed',
            'metadata' => array_merge($this->batch->metadata ?? [], [
                'zip_error' => $e->getMessage(),
                'zip_failed_at' => now()->toIso8601String()
            ])
        ]);
    }
}