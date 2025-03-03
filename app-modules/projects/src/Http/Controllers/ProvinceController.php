<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Http\Requests\ProvinceRequest;
use Modules\Projects\Models\Province;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProvinceController
{
    public function select2() {
        return response()->json(Province::all(), 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $provinces = Province::with('districts')->withCount('districts')->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
            ->orWhere('name_fa', 'like', '%'.$search.'%')
            ->orWhere('name_pa', 'like', '%'.$search.'%')
            ->orWhere('code', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($provinces, 201);
    }
    public function store(ProvinceRequest $request) {
        $data = $request->validated();
        $province = Province::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }


    public function edit($id) {
        $province = Province::find($id);
        $province->districts;
        return response()->json($province, 201);
    }

    public function update(ProvinceRequest $request, $id) {
        $data = $request->validated();
        $province = Province::find($id);
        $province->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $province], 201);
    }

    public function destroy($id) {
        $province = Province::find($id);
        if (!$province) {
            return response()->json(['message' => 'Province not found'], 404);
        }

        if ($province->districts->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete the province because it has associated projects.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $province->delete();

        return response()->json(['message' => 'Province deleted successfully'], 201);
    }
}
