<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Wnx\LaravelBackupRestore\Restore\Restore;
use Storage;

class BackupController extends Controller
{
     /**
     * Perform backup and saves the gzip to the file storage.
     */
    public function backup()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);

            return response()->json([
                'message' => 'Successfully generated backup!',
                'output' => Artisan::output()
            ], 200);
        } catch (\Exception $e) {
            // You can log the full error for debugging
            \Log::error('Database backup failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Backup failed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function uploadBackup(Request $request) {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
    
            $originalName = $file->getClientOriginalName(); // keep original filename
    
            $path = Storage::disk('backups')->putFileAs(
                '',  // no subfolder, just root of the disk
                $file,
                $originalName
            );
    
            return response()->json([
                'message' => 'File uploaded successfully',
                'path' => $path,
            ]);
        }
    
        return response()->json(['message' => 'No file uploaded'], 400);
    }


    public function listBackups()
    {
        // Assuming you're using local disk and backups are stored in 'backups' folder
        $files = Storage::disk('backups')->files();

        return response()->json($files);
    }

    public function deleteBackup(Request $request)
    {
        $disk = Storage::disk('backups');

        if ($disk->exists($request->filename)) {
            $disk->delete($request->filename);
            return response()->json(['message' => 'Backup deleted successfully.'], 201);
        }

        return response()->json(['message' => 'Backup file not found.'], 404);
    }

    public function restoreBackup(Request $request)
    {
        $disk = Storage::disk('backups'); // assumes you configured this disk

        if (!$disk->exists($request->filename)) {
            return response()->json(['message' => 'Backup file not found.'], 404);
        }
        $path = $disk->path($request->filename);
        try {
            
            Artisan::call('backup:restore', [
                '--disk' => 'local', // or 's3', etc.
                '--backup' => 'UN-Habitat-MIS/'.$request->filename, // or the specific backup name
                '--connection' => 'mysql',
                '--password' => 'your-db-password', // optional
                '--reset' => true, // optional: drops all tables first
            ]);

            return response()->json(['message' => 'Backup restored successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Restore failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download($filename)
    {
        $disk = Storage::disk('backups');
        
        if (!$disk->exists($filename)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($disk->path($filename), $filename);
    }
}
