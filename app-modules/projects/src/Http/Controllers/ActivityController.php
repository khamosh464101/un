<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\ActivityRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class ActivityController
{
    public function select2($id = null) {
        $activities;
        if ($id) {
           $project = Project::find($id);
           $activities = $project->activities;

        } else {
            $activities = Activity::select('id', 'title')->get();
        }
        return response()->json($activities, 201);
  
    }

    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $typeId = $request->typeId;
        $statusId = $request->statusId;
        $projectId = $request->projectId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $activities = Activity::with('status')
                    ->with('type')
                    ->with('responsible')
                    ->with('project')
                    ->withCount('tickets')
                    ->when($search, function($query) use ($search) {
                        $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('activity_number', $search);
                    })
                    ->when($statusId, function($query) use ($statusId) {
                        $query->where('activity_status_id', $statusId);
                    })
                    ->when($typeId, function($query) use ($typeId) {
                        $query->where('activity_type_id', $typeId);
                    })
                    ->when($projectId, function($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->orderBy($field, $sortType)
                    ->paginate(8);
        return response()->json($activities, 201);
    }

    public function store(ActivityRequest $request) {
        Gate::authorize('create', Activity::class);
        $data = Arr::except($request->validated(), ['responsibles_id']);
        $activity = Activity::create($data);
        $activity->responsibles()->syncWithoutDetaching($request->responsibles_id);
        $activity->status;
        $activity->type;
        $activity->responsibles;
        return response()->json(['message' => 'Sucessfully added!', 'data' => $activity], 201);
    }

    public function edit($id) {
        $activity = Activity::with('gozars.district.province')->with('tickets.status')->with('logs.causer')->find($id);
        $activity->status;
        $activity->type;
        $activity->documents;
        $activity->project;
        $activity->responsibles;
        $activity->progress = $activity->getProgress();
        return response()->json($activity, 201);
    }

    public function update(ActivityRequest $request, $id) {
        $activity = Activity::find($id);
        Gate::authorize('update', $activity);
        $data = Arr::except($request->validated(), ['responsibles_id']);
        
        $activity->update($data);
        $activity->responsibles()->detach();
        $activity->responsibles()->attach($request->responsibles_id);
        $activity->status;
        $activity->type;
        $activity->responsibles;
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $activity], 201);
    }

    public function destroy($id) {
        $activity = Activity::find($id);
        Gate::authorize('delete', $activity);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        if ($activity->tickets->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this activity because it has associated activities.'
            ], 400);  // Return a 400 Bad Request status
        }

        $activity->delete();
        return response()->json(['message' => 'Activity deleted successfully'], 201);
    }

    public function getLocation($id) {
        $gozars = Gozar::select('id as value', 'name as label', 'district_id')->whereHas('projects', function ($query) use ($id) {
            $query->where('projects.id', $id);
        })->get();
        
        // Get unique Districts from these Villages
        $districts = District::select('id as value', 'name as label', 'province_id')->whereIn('id', $gozars->pluck('district_id')->unique())->get();
        
        // Get unique Provinces from these Districts
        $provinces = Province::select('id as value', 'name as label')->whereIn('id', $districts->pluck('province_id')->unique())->get();
        
        return response()->json([
            'gozars' => $gozars,
            'districts' => $districts,
            'provinces' => $provinces
        ], 201);
        
    }

    public function addGozar(Request $request) {
        $activity = Activity::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        if ($activity->gozars->contains($gozar)) {
            return response()->json(['message' => 'Already exist'], 500);
        }
       $activity->gozars()->attach($gozar);
        $gozar->district->province;
        return response()->json($gozar, 201);

    }

    public function removeGozar(Request $request) {
      
        $activity = Activity::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        $tickets = $gozar->tickets()->where('activity_id', $activity->id)->get();

        if (!$tickets->isEmpty()) {
            return response()->json(['message' => $gozar->name . " has attached tickets from this activity you can't detach it."], 500);
        }
        $activity->gozars()->detach($request->gozar_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }
}
