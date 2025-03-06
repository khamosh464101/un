<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Http\Requests\GozarRequest;
use Modules\Projects\Models\Gozar;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GozarController
{
    public function select2() {
        return response()->json(Gozar::all(), 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $districtId = $request->districtId;
        $provinceId = $request->provinceId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $programs = Gozar::with('district.province')->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
            ->orWhere('name_fa', 'like', '%'.$search.'%')
            ->orWhere('name_pa', 'like', '%'.$search.'%');
        })->when($provinceId, function ($query) use ($provinceId) {
            $query->whereHas('district', function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            });
        })
        ->when($districtId, function($query) use ($districtId){
            $query->where('district_id', $districtId);
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($programs, 201);
    }
    public function store(GozarRequest $request) {
        $data = $request->validated();
        $gozar = Gozar::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }


    public function edit($id) {
        $gozar = Gozar::find($id);
        return response()->json($gozar, 201);
    }

    public function update(GozarRequest $request, $id) {
        $data = $request->validated();
        $gozar = Gozar::find($id);
        $gozar->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $gozar], 201);
    }

    public function destroy($id) {
        $gozar = Gozar::find($id);
        if (!$gozar) {
            return response()->json(['message' => 'Gozar not found'], 404);
        }

        if ($gozar->projects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete the gozar because it has associated projects.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $gozar->delete();

        return response()->json(['message' => 'Gozar deleted successfully'], 201);
    }
}
