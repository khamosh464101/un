<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Http\Requests\ProgramRequest;
use Modules\Projects\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProgramController
{
    public function select2() {
        return response()->json(Program::all(), 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $programs = Program::with('status')->withCount('projects')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($programs, 201);
    }
    public function store(ProgramRequest $request) {
        $data = $request->safe()->except(['logo']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/program/logo', $this->getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        $program = Program::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public static function getFileName($title, $file) {
        $sanitizedFileName = Str::of($title)
        ->replaceMatches('/[^a-zA-Z0-9 ]/', '')
        ->lower()
        ->replaceMatches('/\s+/', ' ')
        ->replace(' ', '-'); 

        return  $sanitizedFileName.'-'. Carbon::now()->format('Y-m-d-H-i-s-v') . '.' . $file->getClientOriginalExtension();
    }

    public function edit($id) {
        $program = Program::with('logs.causer')->find($id);
        $program->status;
        $program->projects;
        $program->documents;
        return response()->json($program, 201);
    }

    public function update(ProgramRequest $request, $id) {
        $data = $request->safe()->except(['logo', '_method']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/program/logo', $this->getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        $program = Program::find($id);
        $program->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $program], 201);
    }

    public function destroy($id) {
        $program = Program::find($id);
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }

        if ($program->projects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete the program because it has associated projects.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        foreach ($program->documents as $key => $document) {
            $document->delete();
        }
        
        $program->delete();

        return response()->json(['message' => 'Program deleted successfully'], 201);
    }

}
