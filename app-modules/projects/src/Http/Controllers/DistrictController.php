<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Http\Requests\DistrictRequest;
use Modules\Projects\Models\District;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DistrictController
{
    public function select2() {
        return response()->json(District::all(), 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $provinceId = $request->provinceId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $programs = District::with('province')->with('gozars')->withCount('gozars')->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
            ->orWhere('name_fa', 'like', '%'.$search.'%')
            ->orWhere('name_pa', 'like', '%'.$search.'%')
            ->orWhere('code', 'like', '%'.$search.'%');
        })
        ->when($provinceId, function($query) use ($provinceId){
            $query->where('province_id', $provinceId);
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($programs, 201);
    }
    public function store(DistrictRequest $request) {
        $data = $request->validated();
        $district = District::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }


    public function edit($id) {
        $district = District::find($id);
        $district->gozars;
        return response()->json($district, 201);
    }

    public function update(DistrictRequest $request, $id) {
        $data = $request->validated();
        $district = District::find($id);
        $district->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $district], 201);
    }

    public function destroy($id) {
        $district = District::find($id);
        if (!$district) {
            return response()->json(['message' => 'District not found'], 404);
        }

        if ($district->gozars->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete the district because it has associated gozars.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $district->delete();

        return response()->json(['message' => 'District deleted successfully'], 201);
    }
}
