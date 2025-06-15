<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Http\Requests\DonorRequest;
use Modules\Projects\Models\Donor;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class DonorController
{
    public function select2() {
        return response()->json(Donor::all(), 201);
    }
    public function index(Request $request) {
        
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $donors = Donor::with('projects')->withCount('projects')->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($donors, 201);
    }

    public function store(DonorRequest $request) {
        Gate::authorize('create', Donor::class);
        $data = $request->validated();
         $donor = Donor::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $donor], 201);
    }

    public function edit($id) {
        $donor = Donor::find($id);
        $donor->logs;
        return response()->json($donor, 201);
    }

    public function update(DonorRequest $request, $id) {
        $donor = Donor::find($id);
        Gate::authorize('update', $donor);
        $data = $request->safe()->except(['_method']);
        
        $donor->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $donor], 201);
    }

    public function destroy($id) {
        $donor = Donor::find($id);
        Gate::authorize('delete', $donor);
        if (!$donor) {
            return response()->json(['message' => 'Donor not found'], 404);
        }

        if ($donor->projects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Donor because it has associated projects.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $donor->delete();

        return response()->json(['message' => 'Donor deleted successfully'], 201);
    }
}
