<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\StaffStatus;
use Modules\Projects\Http\Requests\StaffStatusRequest;

class StaffStatusController
{
    public function select2() {
        return response()->json(StaffStatus::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $statuses = StaffStatus::withCount('staffs')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($statuses, 201);
    }

    public function store(StaffStatusRequest $request) {
        $data = $request->validated();
         $status = StaffStatus::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $status = StaffStatus::find($id);
        return response()->json($status, 201);
    }

    public function update(StaffStatusRequest $request, $id) {
        $data = $request->safe()->except(['_method']);
        $status = StaffStatus::find($id);
        $status->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $status], 201);
    }

    public function destroy($id) {
        $status = StaffStatus::find($id);
        if (!$status) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($status->staffs->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $status->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
