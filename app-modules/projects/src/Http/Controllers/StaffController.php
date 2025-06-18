<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Http\Requests\StaffRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Gate;

use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffController
{
    public function select2($id = null) {
        $responsibles;
        if ($id) {
           $activity = Activity::find($id);
           $responsibles = $activity->responsibles;

        } else {
            $responsibles = Staff::select('id', 'name')->get();
        }
        return response()->json($responsibles, 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $staffs = Staff::with('status')->withCount([
            'activities as projects_count' => function ($query) {
                $query->join('projects', 'projects.id', '=', 'activities.project_id');
            }
        ])->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($staffs, 201);
    }
    
    public function store(StaffRequest $request) {
        Gate::authorize('create', Staff::class);
        $data = $request->safe()->except(['photo']);
        // Handle the file upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProgramController::getFileName($data['name'], $request->file('photo')));
            $data['photo'] = $get_file;
        }


        $staff = Staff::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $staff = Staff::with('logs.causer')->withCount([
            'activities as projects_count' => function ($query) {
                $query->join('projects', 'projects.id', '=', 'activities.project_id');
            }
        ])->withCount('tickets')->find($id);
        $staff->status;
        $staff->contractType;
        if ($staff->user) {
            $staff->user->roles;
        }
       
        $staff->documents;
        return response()->json($staff, 201);
    }

    public function update(StaffRequest $request, $id) {
        $staff = Staff::find($id);
        Gate::authorize('update', $staff);
        $data = $request->safe()->except(['photo', '_method']);
        // Handle the file upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProgramController::getFileName($data['name'], $request->file('photo')));
            $data['photo'] = $get_file;
        }
       
        $staff->update($data);
        if ($staff->user) {
            $staff->user->update([
                'name' => $staff->name,
                'email' => $staff->official_email,
                'phone' => $staff->phone1,
            ]);
        }
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $staff], 201);
    }

    public function destroy($id) {
       
        $staff = Staff::find($id);
        Gate::authorize('delete', $staff);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        if ($staff->manages->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this staff because he/she is responsible for project.'
            ], 400);  // Return a 400 Bad Request status
        }

        if ($staff->activities->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this staff because he/she has associated actihe/sheies.'
            ], 400);  // Return a 400 Bad Request status
        }

        if ($staff->tickets->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this staff because he/she has associated tickets.'
            ], 400);  // Return a 400 Bad Request status
        }
    
        $staff->delete();
        return response()->json(['message' => 'Staff deleted successfully'], 201);
    }
}
