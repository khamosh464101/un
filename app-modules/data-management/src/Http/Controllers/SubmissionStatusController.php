<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Models\SubmissionStatus;
use Modules\DataManagement\Http\Requests\SubmissionStatusRequest;
use Illuminate\Support\Facades\Gate;

class SubmissionStatusController
{
    public function select2() {
        return response()->json(SubmissionStatus::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $statuses = SubmissionStatus::withCount('submissions')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($statuses, 201);
    }

    public function store(SubmissionStatusRequest $request) {
        // Gate::authorize('create', SubmissionStatus::class);
        $data = $request->validated();
         $status = SubmissionStatus::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $status = SubmissionStatus::find($id);
        return response()->json($status, 201);
    }

    public function update(SubmissionStatusRequest $request, $id) {
        $status = SubmissionStatus::find($id);
        // Gate::authorize('update', $status);
        $data = $request->safe()->except(['_method']);
        
        $status->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $status], 201);
    }

    public function destroy($id) {
        $status = SubmissionStatus::find($id);
        // Gate::authorize('delete', $status);
        if (!$status) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($status->submissions->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $status->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
