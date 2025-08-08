<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\SubprojectType;
use Modules\Projects\Http\Requests\SubprojectTypeRequest;
use Illuminate\Support\Facades\Gate;

class SubprojectTypeController
{
    public function select2() {
        return response()->json(SubprojectType::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $types = SubprojectType::withCount('subprojects')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($types, 201);
    }

    public function store(SubprojectTypeRequest $request) {
        Gate::authorize('create', SubprojectType::class);
        $data = $request->validated();
         $type = SubprojectType::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $type = SubprojectType::find($id);
        return response()->json($type, 201);
    }

    public function update(SubprojectTypeRequest $request, $id) {
        $type = SubprojectType::find($id);
        Gate::authorize('update', $type);
        $data = $request->safe()->except(['_method']);
        
        $type->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $type], 201);
    }

    public function destroy($id) {
        $type = SubprojectType::find($id);
        Gate::authorize('delete', $type);
        if (!$type) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($type->subprojects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request type
        }
        
        $type->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
