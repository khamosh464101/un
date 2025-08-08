<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\ActivityType;
use Modules\Projects\Http\Requests\ActivityTypeRequest;
use Illuminate\Support\Facades\Gate;

class ActivityTypeController
{
    public function select2() {
        return response()->json(ActivityType::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $statuses = ActivityType::withCount('activities')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($statuses, 201);
    }

    public function store(ActivityTypeRequest $request) {
        Gate::authorize('create', ActivityType::class);
        
        $data = $request->validated();
         $type = ActivityType::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $type], 201);
    }

    public function edit($id) {
        $status = ActivityType::find($id);
        return response()->json($status, 201);
    }

    public function update(ActivityTypeRequest $request, $id) {
        $status = ActivityType::find($id);
        Gate::authorize('update', $status);
        $data = $request->safe()->except(['_method']);
        
        $status->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $status], 201);
    }

    public function destroy($id) {
        $status = ActivityType::find($id);
        Gate::authorize('delete', $status);
        if (!$status) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($status->activities->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $status->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
