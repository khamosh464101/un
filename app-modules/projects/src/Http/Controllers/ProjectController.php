<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Models\District;
use Modules\Projects\Http\Requests\ProjectRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Projects\Http\Resources\Project\ProjectsResource;
use Illuminate\Support\Facades\Gate;
use Storage; 
use Illuminate\Support\Str;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Models\Form;

class ProjectController
{
    protected $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }
    public function select2() {
  
        return response()->json(Project::select('id', 'title')->get(), 201);
    }

    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $statusId = $request->statusId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $projects = Project::with('status')->with('manager')->withCount('activities')
        ->when($statusId, function($query) use ($statusId) {
            $query->where('project_status_id', $statusId);
        })
        ->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })

        ->orderBy($field, $sortType)->paginate(10);
        return ProjectsResource::collection($projects);
        // return response()->json($projects, 201);
    }

    public function googleStorageDirectories() {
        $folders = Storage::disk('gcs')->directories();
        return response()->json($folders);
    }
    
    public function store(ProjectRequest $request) {
        Gate::authorize('create', Project::class);
        $data = $request->safe()->except(['logo']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/project/logo', $this->getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        $project = Project::create($data);
        $this->addFormToDb($project->kobo_project_id);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $project], 201);
    }

    public function edit($id) {
        $project = Project::with('districts.province')
        ->with(['activities.status', 'activities.type', 'activities.responsibles'])
        ->with('subprojects.type')
        ->with('subprojects.partner')
        ->with('logs.causer')->find($id);
        $project->status;
        $project->documents;
        $project->donor;
        $project->staff;
        $project->manager;
        $project->gozars;
        $project->subprojects;
        $project->progress = $project->getProgress();
        return response()->json($project, 201);
    }

    public function update(ProjectRequest $request, $id) {
        $project = Project::find($id);
        Gate::authorize('update', $project);
        $data = $request->safe()->except(['logo', '_method']);
        // Handle the file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            
            $get_file = $request->file('logo')->storeAs('project-management/project/logo', $this->getFileName($data['title'], $request->file('logo')));
            $data['logo'] = $get_file;
        }
        
        $project->update($data);
        $this->addFormToDb($project->kobo_project_id);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $project], 201);
    }

    public function destroy($id) {
        $project = Project::find($id);
        Gate::authorize('delete', $project);
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
        $district = District::find($request->district_id);
        
        $project->districts()->syncWithoutDetaching([$district]);
        $project->gozars()->syncWithoutDetaching($request->gozars_id);
        
       
    
        return response()->json($project->load('gozars')->load('districts.province'), 201);

    }

    public function editGozar(Request $request) {
        $project = Project::find($request->id);
        if ($request->district_id !== $request->prv_district_id) {
            $project->districts()->detach([$request->prv_district_id]);
            $project->districts()->syncWithoutDetaching([$request->district_id]);
        }
        
        $diffs = array_diff($request->prv_gozars_id, $request->gozars_id);

        $project->gozars()->detach($diffs);
        $project->gozars()->syncWithoutDetaching($request->gozars_id);
        return response()->json($project->load('gozars')->load('districts.province'), 201);
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

    public function removeDistrict(Request $request) {
        $project = Project::find($request->id);
        $district = District::find($request->district_id);
        foreach ($project->gozars as $key => $value) {
            if ($value->district_id === $request->district_id ) {
                $project->gozars()->detach($value->id);
            }
        }

        $project->districts()->detach($request->district_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }

     public static function getFileName($title, $file) {
        $sanitizedFileName = Str::of($title)
        ->replaceMatches('/[^a-zA-Z0-9 ]/', '')
        ->lower()
        ->replaceMatches('/\s+/', ' ')
        ->replace(' ', '-'); 

        return  $sanitizedFileName.'-'. Carbon::now()->format('Y-m-d-H-i-s-v') . '.' . $file->getClientOriginalExtension();
    }

    protected function addFormToDb($formId) {
        $forms = $this->kobo->getFormDetails($formId);
        if ($forms) {
            $form = Form::where('form_id', $formId)?->first();
            if ($form) {
                $form->update(['raw_schema' => $forms]);
            } else {
                Form::create(['form_id' => $formId,'raw_schema' => $forms]);
            }
        }
        
    }
}