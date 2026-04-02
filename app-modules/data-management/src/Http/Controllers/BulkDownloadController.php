<?php

namespace Modules\DataManagement\Http\Controllers;


use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Models\BulkDownloadBatch;
use Modules\DataManagement\Models\BulkDownloadItem;
use Modules\DataManagement\Models\BulkDownloadLog;
use Modules\DataManagement\Jobs\ProcessBulkDownloadItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

use Modules\DataManagement\Http\Controllers\SubmissionController;
use Modules\Projects\Models\Project;

class BulkDownloadController
{

    public function index(Request $request) {
        $search = $request->search;

        $zips = BulkDownloadBatch::when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($zips, 201);
    }

    public function startBulkDownload(Request $request) {
        $ids = $request->submissions;
        $batchName = $request->batchName;
        $request->request->remove('batchName');
        if ($request->selectAll === true) {
            $query = Submission::query();
            if ($request->project_id) {
                // Records attached to the given project
                $query->whereHas('projects', function ($q) use ($request) {
                    $q->where('projects.id', $request->project_id);
                });
            } else {
                // Records that are not attached to any project
                $query->whereDoesntHave('projects');
            }
            SubmissionController::getSearchData($query, $request);
            $ids = $query->pluck('id')->toArray();
        } 

        $batchId = (string) Str::uuid();

        DB::beginTransaction();
        
        try {
            // Create batch record
            $batch = BulkDownloadBatch::create([
                'batch_id' => $batchId,
                'name' => $request->batch_name ?? 'Bulk Download ' . now()->format('Y-m-d H:i:s'),
                'total_items' => count($ids),
                'status' => 'pending',
                'metadata' => [
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'requested_at' => now()->toIso8601String()
                ]
            ]);

            // Create individual items
            foreach ($ids as $submissionId) {
                $item = BulkDownloadItem::create([
                    'batch_id' => $batchId,
                    'submission_id' => $submissionId,
                    'status' => 'pending',
                    'progress' => 0
                ]);

                // Dispatch job for each item
                ProcessBulkDownloadItem::dispatch($batchId, $submissionId, $item->id)
                    ->onQueue('bulk-downloads');
            }

            BulkDownloadLog::create([
                'batch_id' => $batchId,
                'level' => 'info',
                'message' => 'Bulk download started',
                'context' => [
                    'total_items' => count($ids),
                    'submission_ids' => $ids
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'total' => count($ids),
                'message' => 'Bulk download started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start bulk download',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    public function startBulkDownloadTest(Request $request)
    {
        $request->validate([
            'submission_ids' => 'required|array',
            'submission_ids.*' => 'exists:dm_submissions,id',
            'batch_name' => 'nullable|string|max:255'
        ]);

        $submissionIds = $request->submission_ids;
        $batchId = (string) Str::uuid();

        DB::beginTransaction();
        
        try {
            // Create batch record
            $batch = BulkDownloadBatch::create([
                'batch_id' => $batchId,
                'name' => $request->batch_name ?? 'Bulk Download ' . now()->format('Y-m-d H:i:s'),
                'total_items' => count($submissionIds),
                'status' => 'pending',
                'metadata' => [
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System',
                    'requested_at' => now()->toIso8601String()
                ]
            ]);

            // Create individual items
            foreach ($submissionIds as $submissionId) {
                $item = BulkDownloadItem::create([
                    'batch_id' => $batchId,
                    'submission_id' => $submissionId,
                    'status' => 'pending',
                    'progress' => 0
                ]);

                // Dispatch job for each item
                ProcessBulkDownloadItem::dispatch($batchId, $submissionId, $item->id)
                    ->onQueue('bulk-downloads');
            }

            BulkDownloadLog::create([
                'batch_id' => $batchId,
                'level' => 'info',
                'message' => 'Bulk download started',
                'context' => [
                    'total_items' => count($submissionIds),
                    'submission_ids' => $submissionIds
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'total' => count($submissionIds),
                'message' => 'Bulk download started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start bulk download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProgress($batchId)
    {
        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $items = BulkDownloadItem::where('batch_id', $batchId)
            ->select('id', 'submission_id', 'status', 'progress', 'file_name', 'error_message', 'updated_at')
            ->orderBy('id')
            ->get();

        // Calculate statistics
        $stats = [
            'total' => $batch->total_items,
            'processed' => $batch->processed_items,
            'completed' => $batch->successful_items,
            'failed' => $batch->failed_items,
            'pending' => $items->where('status', 'pending')->count(),
            'processing' => $items->where('status', 'processing')->count()
        ];

        $percentage = $batch->total_items > 0 
            ? round(($batch->processed_items / $batch->total_items) * 100) 
            : 0;

        // Get recent logs
        $recentLogs = BulkDownloadLog::where('batch_id', $batchId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'batch_name' => $batch->name,
            'status' => $batch->status,
            'stats' => $stats,
            'percentage' => $percentage,
            'items' => $items,
            'logs' => $recentLogs,
            'started_at' => $batch->started_at,
            'completed_at' => $batch->completed_at,
            'created_at' => $batch->created_at
        ]);
    }

    public function downloadBatch($batchId)
    {
        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        if ($batch->status !== 'completed') {
            return response()->json(['error' => 'Batch is not ready for download'], 400);
        }

        // If ZIP already exists, return it
        if ($batch->zip_file_path && Storage::disk('public')->exists($batch->zip_file_path)) {
            return response()->download(
                Storage::disk('public')->path($batch->zip_file_path),
                $batch->name . '.zip'
            );
        }

        // Get all completed files
        $completedFiles = BulkDownloadItem::where('batch_id', $batchId)
            ->where('status', 'completed')
            ->whereNotNull('file_path')
            ->get();

        if ($completedFiles->isEmpty()) {
            return response()->json(['error' => 'No completed files found'], 404);
        }

        // Create ZIP archive
        $zipFileName = Str::slug($batch->name) . '-' . $batchId . '.zip';
        $zipPath = storage_path("app/temp/{$zipFileName}");
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Could not create ZIP file'], 500);
        }

        foreach ($completedFiles as $file) {
            $fullPath = Storage::disk('public')->path($file->file_path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->file_name ?? basename($file->file_path));
            }
        }

        $zip->close();

        // Store ZIP path in batch
        $zipStoragePath = "bulk-downloads/zips/{$zipFileName}";
        Storage::disk('public')->put($zipStoragePath, file_get_contents($zipPath));
        $batch->update(['zip_file_path' => $zipStoragePath]);

        // Clean up temp file
        @unlink($zipPath);

        return response()->download(
            Storage::disk('public')->path($zipStoragePath),
            $batch->name . '.zip'
        );
    }

    public function getFailedItems($batchId)
    {
        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $failedItems = BulkDownloadItem::where('batch_id', $batchId)
            ->where('status', 'failed')
            ->with('submission:id') // Add relevant fields
            ->get()
            ->map(function ($item) {
                  return [
                    'id' => $item->id,
                    'submission_id' => $item->submission_id,
                    'province_code' => $item->submission->sourceInformation->province_code ?? 'N/A',
                    'city_code' => $item->submission->sourceInformation->city_code ?? 'N/A',
                    'district_code' => $item->submission->sourceInformation->district_code ?? 'N/A',
                    'guzar_code' => $item->submission->sourceInformation->kbl_guzar_number ?? 'N/A',
                    'block_number' => substr($item->submission->sourceInformation->block_number, -3) ?? 'N/A',
                    'house_number' => substr($item->submission->sourceInformation->house_number, -3) ?? 'N/A',
                    'error' => $item->error_message,
                    'attempted_at' => $item->completed_at,
                    'metadata' => $item->metadata
                ];
            });

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'failed_count' => $failedItems->count(),
            'failed_items' => $failedItems
        ]);
    }

    public function retryFailed(Request $request, $batchId)
    {
        $request->validate([
            'item_ids' => 'sometimes|array',
            'item_ids.*' => 'exists:dm_bulk_download_items,id'
        ]);

        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $query = BulkDownloadItem::where('batch_id', $batchId)->where('status', 'failed');
        
        if ($request->has('item_ids')) {
            $query->whereIn('id', $request->item_ids);
        }
        
        $failedItems = $query->get();

        if ($failedItems->isEmpty()) {
            return response()->json(['error' => 'No failed items to retry'], 400);
        }

        DB::transaction(function () use ($failedItems, $batch) {
            foreach ($failedItems as $item) {
                // Reset item
                $item->update([
                    'status' => 'pending',
                    'progress' => 0,
                    'error_message' => null,
                    'started_at' => null,
                    'completed_at' => null
                ]);

                // Update batch counters
                $batch->decrement('failed_items');
                $batch->decrement('processed_items');

                // Re-dispatch job
                ProcessBulkDownloadItem::dispatch($batch->batch_id, $item->submission_id, $item->id)
                    ->onQueue('bulk-downloads');
            }

            // Update batch status if needed
            if ($batch->status === 'completed') {
                $batch->update(['status' => 'processing']);
            }
        });

        BulkDownloadLog::create([
            'batch_id' => $batchId,
            'level' => 'info',
            'message' => "Retrying {$failedItems->count()} failed items",
            'context' => ['item_ids' => $failedItems->pluck('id')]
        ]);

        return response()->json([
            'success' => true,
            'message' => "Retrying {$failedItems->count()} failed items",
            'retry_count' => $failedItems->count()
        ]);
    }

    public function cancelBatch($batchId)
    {
        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        if (!in_array($batch->status, ['pending', 'processing'])) {
            return response()->json(['error' => 'Batch cannot be cancelled in its current state'], 400);
        }

        DB::transaction(function () use ($batch) {
            $batch->update(['status' => 'cancelled']);
            
            // Mark pending items as cancelled
            BulkDownloadItem::where('batch_id', $batch->batch_id)
                ->whereIn('status', ['pending', 'processing'])
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Cancelled by user',
                    'completed_at' => now()
                ]);

            // Update counters
            $batch->updateCounters();
        });

        // Clean up any existing files
        Storage::disk('public')->deleteDirectory("bulk-downloads/batch-{$batchId}");

        BulkDownloadLog::create([
            'batch_id' => $batchId,
            'level' => 'warning',
            'message' => 'Batch cancelled by user'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch cancelled successfully'
        ]);
    }

    public function getBatchList(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|in:pending,processing,completed,failed,cancelled'
        ]);

        $query = BulkDownloadBatch::orderBy('created_at', 'desc');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'batches' => $batches
        ]);
    }

    public function cleanupOldBatches()
    {
        // Delete batches older than 7 days
        $oldBatches = BulkDownloadBatch::where('created_at', '<', now()->subDays(7))
            ->where('status', 'completed')
            ->get();

        foreach ($oldBatches as $batch) {
            // Delete files
            Storage::disk('public')->deleteDirectory("bulk-downloads/batch-{$batch->batch_id}");
            if ($batch->zip_file_path) {
                Storage::disk('public')->delete($batch->zip_file_path);
            }
            
            // Delete logs and items (cascading)
            BulkDownloadLog::where('batch_id', $batch->batch_id)->delete();
            BulkDownloadItem::where('batch_id', $batch->batch_id)->delete();
            $batch->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cleaned up ' . $oldBatches->count() . ' old batches'
        ]);
    }

     public function destroy($id)
    {
        $batch = BulkDownloadBatch::find($id);
        
        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $batch->delete();

         return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Batch cancelled successfully'
        ]);
    }
}
