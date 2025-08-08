<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\ProjectStatus;
use Modules\Projects\Http\Requests\ProjectStatusRequest;
use Illuminate\Support\Facades\Gate;

class ProjectStatusController
{
    public function select2() {
        return response()->json(ProjectStatus::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $statuses = ProjectStatus::withCount('projects')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($statuses, 201);
    }

    public function store(ProjectStatusRequest $request) {
        Gate::authorize('create', ProjectStatus::class);
        $data = $request->validated();
         $status = ProjectStatus::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $status = ProjectStatus::find($id);
        return response()->json($status, 201);
    }

    public function update(ProjectStatusRequest $request, $id) {
        $status = ProjectStatus::find($id);
        Gate::authorize('update', $status);
        $data = $request->safe()->except(['_method']);
        
        $status->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $status], 201);
    }

    public function destroy($id) {
        $status = ProjectStatus::find($id);
        Gate::authorize('update', $status);
        if (!$status) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($status->projects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $status->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
