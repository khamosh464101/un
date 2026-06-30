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
            $missingFiles = [];
            $usedNames = [];
            foreach ($completedFiles as $file) {
                // Download PDF from GCS to temp for zipping
                if (Storage::disk('gcs')->exists($file->file_path)) {
                    $pdfContent = Storage::disk('gcs')->get($file->file_path);
                    
                    // Prevent duplicate filenames in ZIP (append submission_id if conflict)
                    $fileName = $file->file_name ?? basename($file->file_path);
                    if (isset($usedNames[$fileName])) {
                        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        $base = pathinfo($fileName, PATHINFO_FILENAME);
                        $fileName = $base . '-' . $file->submission_id . '.' . $ext;
                    }
                    $usedNames[$fileName] = true;
                    
                    $tempPdfPath = $tempDir . '/' . $fileName;
                    file_put_contents($tempPdfPath, $pdfContent);
                    $zip->addFile($tempPdfPath, $fileName);
                    $fileCount++;
                } else {
                    $missingFiles[] = $file->file_path;
                    // Mark item as failed since file is missing
                    $file->update([
                        'status' => 'failed',
                        'error_message' => 'PDF file missing from storage during ZIP generation'
                    ]);
                    $this->log('warning', 'File not found on GCS — marked as failed', [
                        'file_path' => $file->file_path,
                        'submission_id' => $file->submission_id
                    ]);
                }
            }
            
            if (!empty($missingFiles)) {
                $this->log('warning', 'Some files were missing during ZIP generation', [
                    'expected' => $completedFiles->count(),
                    'actual_in_zip' => $fileCount,
                    'missing_count' => count($missingFiles)
                ]);
                // Update batch counters to reflect newly failed items
                $this->batch->successful_items = $this->batch->successful_items - count($missingFiles);
                $this->batch->failed_items = $this->batch->failed_items + count($missingFiles);
                $this->batch->save();
            }
            
            $zip->close();
            
            // Clean up temp PDF files
            $tempFiles = glob($tempDir . '/*.pdf');
            foreach ($tempFiles as $tempFile) {
                @unlink($tempFile);
            }
            
            $this->log('info', 'ZIP file created', ['file_count' => $fileCount, 'size' => filesize($zipPath)]);
            
            // Upload ZIP to Google Cloud Storage
            $zipStoragePath = "bulk-downloads/zips/{$zipFileName}";
            Storage::disk('gcs')->put($zipStoragePath, file_get_contents($zipPath));
            
            // Update batch with ZIP path
            $this->batch->update([
                'zip_file_path' => $zipStoragePath,
                'status' => 'completed'
            ]);
            
            // Clean up temp ZIP file
            @unlink($zipPath);
            
            $this->log('info', 'ZIP generation completed and uploaded to GCS', ['zip_path' => $zipStoragePath]);
            
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