<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Partner;
use Modules\Projects\Models\Subproject;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\SubprojectRequest;

class SubprojectController
{
    public function select2() {
       
        return response()->json(Subproject::select('id', 'title')->get(), 201);
    }

    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $typeId = $request->typeId;
        $partnerId = $request->partnerId;
        $projectId = $request->projectId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $subprojects = Subproject::with('type')
        ->with('project')
        ->with('partner')
        ->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%')
                ->orWhere('announcement_date', $search)
                ->orWhere('date_of_contract', $search);
              
        })
        ->when($partnerId, function($query) use ($partnerId) {
            $query->where('partner_id', $partnerId);
        })
        ->when($typeId, function($query) use ($typeId) {
            $query->where('subproject_type_id', $typeId);
        })
        ->when($projectId, function($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
        ->orderBy($field, $sortType)->paginate(8);
        return response()->json($subprojects, 201);
    }
    
    public function store(SubprojectRequest $request) {
        $data = $request->validated();

        $subproject = Subproject::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $subproject], 201);
    }

    public function edit($id) {
        $subproject = Subproject::with('gozars.district.province')->with('logs.causer')->find($id);
        $subproject->partner;
        $subproject->project;
        $subproject->type;
        $subproject->documents;
        return response()->json($subproject, 201);
    }

    public function update(SubprojectRequest $request, $id) {
        $data = $request->validated();
        $subproject = Subproject::find($id);
        $subproject->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $subproject], 201);
    }

    public function destroy($id) {
       
        $subporject = Subproject::find($id);
        if (!$subporject) {
            return response()->json(['message' => 'Subproject not found'], 404);
        }
        $subporject->delete();
        return response()->json(['message' => 'Subproject deleted successfully'], 201);
    }

    public function addGozar(Request $request) {
        $subproject = Subproject::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        if ($subproject->gozars->contains($gozar)) {
            return response()->json(['message' => 'Already exist'], 500);
        }
       $subproject->gozars()->attach($gozar);
        $gozar->district->province;
        return response()->json($gozar, 201);

    }

    public function removeGozar(Request $request) {
        $subproject = Subproject::find($request->id);
        $subproject->gozars()->detach($request->gozar_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }
}
