<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\ActivityRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController
{
    public function select2() {
  
        return response()->json(Activity::select('id', 'title')->get(), 201);
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
        $data = $request->validated();
        $activity = activity::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $activity], 201);
    }

    public function edit($id) {
        $activity = Activity::with('gozars.district.province')->find($id);
        $activity->status;
        $activity->tickets;
        $activity->types;
        $activity->logs;
        $activity->documents;
        $activity->project;
        $activity->responsible;
        return response()->json($activity, 201);
    }

    public function update(ActivityRequest $request, $id) {
        $data = $request->validated();
        $activiry = Activity::find($id);
        $activiry->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $activiry], 201);
    }

    public function destroy($id) {
        $activity = Activity::find($id);
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
}
