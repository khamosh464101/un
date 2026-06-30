<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\DataManagement\Models\BulkDownloadBatch;
use Modules\DataManagement\Models\BulkDownloadItem;

class CheckMissingSurveyPdfs extends Command
{
    protected $signature = 'batch:check-missing {batch_id : The UUID of the batch to check}';
    protected $description = 'Check a bulk download batch for completed items whose PDF files are missing from GCS';

    public function handle()
    {
        $batchId = $this->argument('batch_id');

        $batch = BulkDownloadBatch::where('batch_id', $batchId)->first();

        if (!$batch) {
            $this->error("Batch not found: {$batchId}");
            return 1;
        }

        $this->info("Batch: {$batch->name}");
        $this->info("Status: {$batch->status}");
        $this->info("Total: {$batch->total_items} | Completed: {$batch->successful_items} | Failed: {$batch->failed_items}");
        $this->newLine();

        $completedItems = BulkDownloadItem::where('batch_id', $batchId)
            ->where('status', 'completed')
            ->whereNotNull('file_path')
            ->get();

        $this->info("Checking {$completedItems->count()} completed items against GCS...");
        $this->newLine();

        $missing = [];
        $bar = $this->output->createProgressBar($completedItems->count());
        $bar->start();

        foreach ($completedItems as $item) {
            if (!Storage::disk('gcs')->exists($item->file_path)) {
                $missing[] = [
                    'id' => $item->id,
                    'submission_id' => $item->submission_id,
                    'file_name' => $item->file_name,
                    'file_path' => $item->file_path,
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (empty($missing)) {
            $this->info('✅ All completed items have their PDF files on GCS. No issues found.');
            return 0;
        }

        $this->warn("⚠️  Found " . count($missing) . " completed items with MISSING PDF files:");
        $this->newLine();

        $this->table(
            ['Item ID', 'Submission ID', 'File Name', 'Expected Path'],
            $missing
        );

        $this->newLine();

        if ($this->confirm('Do you want to mark these items as failed?', false)) {
            foreach ($missing as $item) {
                BulkDownloadItem::where('id', $item['id'])->update([
                    'status' => 'failed',
                    'error_message' => 'PDF file missing from GCS (detected by batch:check-missing)',
                ]);
            }

            // Update batch counters
            $batch->successful_items = $batch->successful_items - count($missing);
            $batch->failed_items = $batch->failed_items + count($missing);
            $batch->save();

            $this->info("Marked " . count($missing) . " items as failed. Batch counters updated.");
        }

        return 0;
    }
}
