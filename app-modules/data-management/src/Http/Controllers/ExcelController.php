<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\Submission;
use App\Imports\MultiTableImport;
use App\Models\Setting;

class ExcelController
{
    public function uploadFile(Request $request) {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
    
            $originalName = $file->getClientOriginalName(); // keep original filename
    
            $path = Storage::disk('excel')->putFileAs(
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


    public function list()
    {
        // Assuming you're using local disk and backups are stored in 'backups' folder
        $files = Storage::disk('excel')->files();

        return response()->json($files);
    }

    public function delete(Request $request)
    {
        
        $disk = Storage::disk('excel');

        if ($disk->exists($request->filename)) {
            $disk->delete($request->filename);
            return response()->json(['message' => 'Backup deleted successfully.'], 201);
        }

        return response()->json(['message' => 'Backup file not found.'], 404);
    }

    public function insert(Request $request)
    {
        // return storage_path('app/private/excel/wochtangi_final.xlsx');
        logger()->info('Memory usage: ' . (memory_get_usage(true)/1024/1024) . ' MB');
        $disk = Storage::disk('excel');
        
        if (!$disk->exists($request->filename)) {
            return response()->json(['message' => 'Excel file not found.'], 404);
        }

        $path = $disk->path($request->filename);
        $startRow = intval($request->startRow ?? 2);
        $limit = intval($request->limitRow ?? 100);
        
        // Configure with larger chunk size
        $import = new MultiTableImport($startRow, $limit, 5, $request->projectId); // Process 50 rows per chunk
        
        // Import with progress monitoring
        Excel::import($import, $path);
        logger()->info('Memory usage: ' . (memory_get_usage(true)/1024/1024) . ' MB');
        return response()->json([
            'message' => 'Import started successfully',
        ], 201);
    }


    public function download($filename)
    {
        $disk = Storage::disk('excel');
        
        if (!$disk->exists($filename)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($disk->path($filename), $filename);
    }
}
