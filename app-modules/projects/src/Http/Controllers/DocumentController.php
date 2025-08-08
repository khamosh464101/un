<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use File;
use Modules\Projects\Models\Document;
use Modules\Projects\Models\Program;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Models\Subproject;
use Modules\Projects\Models\Partner;
use Modules\Projects\Http\Requests\DocumentRequest;
use Storage;
use DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentController
{
    public function store(DocumentRequest $request) {
    
        $data = $request->safe()->except(['id', 'imgId', 'type']);
        $filePath = storage_path('app/public/tmp/' . $request->imgId);
            
        if (File::exists($filePath)) {
            $size = File::size($filePath);
            $data['size'] = ($size / 1048576);
            $fileContents = File::get($filePath);
            $permanentPath = 'project-management/document/'.$this->getFileName($data['title'], $filePath);
 
            Storage::put($permanentPath, $fileContents);
            File::delete(storage_path($filePath));
            $data['path'] = $permanentPath;
            
        }
        
        if ($request->type == 'Program') {
            $program = Program::find($request->id);
            $document = $program->documents()->create($data);
            return response()->json($document, 201);
        }
        if ($request->type == 'Project') {
            $project = Project::find($request->id);
            $document = $project->documents()->create($data);
            return response()->json($document, 201);
        }
        if ($request->type == 'Partner') {
            $partner = Partner::find($request->id);
            $document = $partner->documents()->create($data);
            return response()->json($document, 201);
        }
        if ($request->type == 'Subproject') {
            $subproject = Subproject::find($request->id);
            $document = $subproject->documents()->create($data);
            return response()->json($document, 201);
        }
        if ($request->type == 'Staff') {
            $staff = Staff::find($request->id);
            $document = $staff->documents()->create($data);
            return response()->json($document, 201);
        }

        if ($request->type == 'Activity') {
            $activity = Activity::find($request->id);
            $document = $activity->documents()->create($data);
            return response()->json($document, 201);
        }

        if ($request->type == 'Ticket') {
            $ticket = Ticket::find($request->id);
            $document = $ticket->documents()->create($data);
            return response()->json($document, 201);
        }
    }

    public function destroy($id) {
        $document = Document::find($id);
        $document->delete();
        return response()->json(['message' => 'Document deleted successfully'], 201);
    }

    public static function getFileName($title, $filePath) {
        $sanitizedFileName = Str::of($title)
        ->replaceMatches('/[^a-zA-Z0-9 ]/', '')
        ->lower()
        ->replaceMatches('/\s+/', ' ')
        ->replace(' ', '-'); 
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        return  $sanitizedFileName.'-'. Carbon::now()->format('Y-m-d-H-i-s-v') . '.' . $fileExtension;
    }

    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20048', // Max 2MB file size
        ]);

        // Store the file in the 'tmp' directory or a temporary storage path
        $file = $request->file('file');
        $fileName = uniqid() . '-' . $file->getClientOriginalName();
        $filePath = $file->storeAs('tmp', $fileName, 'public');

        // Return the unique file ID (filename or something similar)
        return response($fileName, 200);
    }

        // Remove: Delete a file from server
        public function remove($id)
        {
            $filePath = storage_path('app/public/tmp/' . $id);
            
            if (File::exists($filePath)) {
                File::delete($filePath);
                return response()->json(['message' => 'File removed successfully']);
            }
    
            return response()->json(['message' => 'File not found'], 404);
        }

           // Restore: Restore a previously uploaded file
        public function restore($id)
        {
            // For example, we could simply return the file's URL or path
            $filePath = storage_path('app/public/tmp/' . $id);

            if (File::exists($filePath)) {
                return response()->download($filePath);
            }

            return response()->json(['message' => 'File not found'], 404);
        }
        public function load($id)
        {
            $filePath = storage_path('app/public/tmp/' . $id);
    
            if (File::exists($filePath)) {
                return response()->download($filePath);
            }
    
            return response()->json(['message' => 'File not found'], 404);
        }

        public function download($id)
        {
            $document = Document::find($id);
            $filePath = storage_path('app/public/' . $document->path);
      
    
            if (File::exists($filePath)) {
                return response()->download($filePath);
            }
            return response()->json(['message' => 'File not found'], 404);
            
            // return response()->download($filepath, $document->path, [
            //     'Content-Type' => 'application/octet-stream'
            // ]);
        }
}
