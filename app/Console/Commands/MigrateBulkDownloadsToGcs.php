<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class MigrateBulkDownloadsToGcs extends Command
{
    protected $signature = 'bulk-downloads:migrate-to-gcs {--dry-run : Show what would be uploaded without actually uploading}';
    protected $description = 'Migrate existing local bulk-downloads files from storage/app/public/bulk-downloads to Google Cloud Storage';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $localBasePath = storage_path('app/public/bulk-downloads');

        if (!File::isDirectory($localBasePath)) {
            $this->warn('No local bulk-downloads directory found at: ' . $localBasePath);
            return 0;
        }

        $files = File::allFiles($localBasePath);

        if (empty($files)) {
            $this->info('No files found in local bulk-downloads directory.');
            return 0;
        }

        $this->info('Found ' . count($files) . ' file(s) to migrate.');

        if ($dryRun) {
            $this->warn('DRY RUN - no files will be uploaded.');
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $file) {
            $relativePath = 'bulk-downloads/' . str_replace($localBasePath . '/', '', $file->getPathname());

            if ($dryRun) {
                $this->newLine();
                $this->line("  Would upload: {$relativePath}");
                $bar->advance();
                continue;
            }

            try {
                // Check if already exists on GCS
                if (Storage::disk('gcs')->exists($relativePath)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Upload to GCS
                Storage::disk('gcs')->put($relativePath, File::get($file->getPathname()));
                $uploaded++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Failed to upload {$relativePath}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete:");
        $this->info("  Uploaded: {$uploaded}");
        $this->info("  Skipped (already exists): {$skipped}");
        $this->info("  Failed: {$failed}");

        if (!$dryRun && $failed === 0 && $uploaded > 0) {
            if ($this->confirm('All files uploaded successfully. Delete local bulk-downloads directory?', false)) {
                File::deleteDirectory($localBasePath);
                $this->info('Local bulk-downloads directory deleted.');
            }
        }

        return 0;
    }
}
