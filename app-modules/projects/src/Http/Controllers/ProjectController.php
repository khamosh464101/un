<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\ProjectRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Projects\Http\Resources\Project\ProjectsResource;

class ProjectController
{
    public function select2() {
  
        return response()->json(Project::select('id', 'title')->get(), 201);
    }

    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $projects = Project::with('status')->with('staff')->withCount('activities')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return ProjectsResource::collection($projects);
        // return response()->json($projects, 201);
    }
    
    public function store(ProjectRequest $request) {
        $data = $request->safe()->except(['logo']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/project/logo', ProgramController::getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        $program = Project::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $project = Project::with('gozars.district.province')->with('activities.status')->with('logs.causer')->find($id);
        $project->status;
        $project->program;
        $project->documents;
        $project->donor;
        $project->staff;
        $project->manager;
        $project->progress = $project->getProgress();
        return response()->json($project, 201);
    }

    public function update(ProjectRequest $request, $id) {
        $data = $request->safe()->except(['logo', '_method']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/project/logo', ProgramController::getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        $project = Project::find($id);
        $project->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $project], 201);
    }

    public function destroy($id) {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        if ($project->activities->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this project because it has associated activities.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        foreach ($project->documents as $key => $document) {
            $document->delete();
        }
    
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully'], 201);
    }

    

    public function addMember(Request $request) {
        $project = Project::find($request->id);
        $staff = Staff::find($request->staff_id);
        
        if ($project->staff->contains($staff)) {
            return response()->json(['message' => 'Already exist'], 500);
        }
        $project->staff()->attach($staff);
        return response()->json($staff, 201);

    }

    public function removeMember(Request $request) {
        $project = Project::find($request->id);
        $staff = Staff::find($request->staff_id);

       $tickets = $staff->tickets()->whereHas('activity', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })->get();

        if (!$tickets->isEmpty()) {
            return response()->json(['message' => $staff->name . " has assigned ticket from this project you can't detach him/her."], 500);
        }

        $project->staff()->detach($request->staff_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }

    public function addGozar(Request $request) {
        $project = Project::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        if ($project->gozars->contains($gozar)) {
            return response()->json(['message' => 'Already exist'], 500);
        }
       $project->gozars()->attach($gozar);
        $gozar->district->province;
        return response()->json($gozar, 201);

    }

    public function removeGozar(Request $request) {
        $project = Project::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        $activities = $gozar->activities()->where('project_id', $project->id)->get();

        if (!$activities->isEmpty()) {
            return response()->json(['message' => $gozar->name . " has attached activities from this project you can't detach it."], 500);
        }
        $project->gozars()->detach($request->gozar_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }
}