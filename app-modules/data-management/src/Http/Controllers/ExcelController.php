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
        
        $disk = Storage::disk('excel'); // assumes you configured this disk
        

        if (!$disk->exists($request->filename)) {
            return response()->json(['message' => 'Excel file not found.'], 404);
        }
        $path = $disk->path($request->filename);
        $schema = json_decode(Form::first()->raw_schema);
        $survey = $schema->asset->content->survey;

        $submission = (new Submission)->getIgnoreIdFillable();
        $submission_labels = [];
        foreach ($submission as $key => $value) {
            foreach ($survey as $key => $val) {
                if (isset($val->name) && $val->name === $value) {
                    array_push($submission_labels, $val);
                    break;
                }
            }
        }
        

        if (!File::exists($path)) {
            return 'File not found.';
        }
        $startRow = intval($request->startRow);
        $limit = intval($request->limitRow);
        $path = storage_path('app/private/excel/wochtangi_final.xlsx');

        Excel::import(new MultiTableImport($startRow, $limit), $path);

        return response()->json(['message' => 'Successfully inserted'], 201);
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
