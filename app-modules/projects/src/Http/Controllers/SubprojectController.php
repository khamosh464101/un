<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Partner;
use Modules\Projects\Models\Subproject;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\SubprojectRequest;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('create', Subproject::class);
        $data = $request->validated();

        $subproject = Subproject::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $subproject], 201);
    }

    public function edit($id) {
        $subproject = Subproject::with('districts.province')->with('logs.causer')->find($id);

        $subproject->partner;
        $subproject->project;
        $subproject->type;
        $subproject->documents;
        $subproject->gozars;
        return response()->json($subproject, 201);
    }

    public function update(SubprojectRequest $request, $id) {
        $subproject = Subproject::find($id);
        Gate::authorize('update', $subproject);
        $data = $request->validated();
        
        $subproject->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $subproject], 201);
    }

    public function destroy($id) {
        $subporject = Subproject::find($id);
        Gate::authorize('delete', $subporject);
        if (!$subporject) {
            return response()->json(['message' => 'Subproject not found'], 404);
        }
        $subporject->delete();
        return response()->json(['message' => 'Subproject deleted successfully'], 201);
    }

    public function addGozar(Request $request) {
        $subproject = Subproject::find($request->id);
        $district = District::find($request->district_id);
        
        $subproject->districts()->syncWithoutDetaching([$district]);
        $subproject->gozars()->syncWithoutDetaching($request->gozars_id);
        return response()->json($subproject->load('gozars')->load('districts.province'), 201);
    }

    public function editGozar(Request $request) {
        $subproject = Subproject::find($request->id);
        if ($request->district_id !== $request->prv_district_id) {
            $subproject->districts()->detach([$request->prv_district_id]);
            $subproject->districts()->syncWithoutDetaching([$request->district_id]);
        }
        
        $diffs = array_diff($request->prv_gozars_id, $request->gozars_id);

        $subproject->gozars()->detach($diffs);
        $subproject->gozars()->syncWithoutDetaching($request->gozars_id);
        return response()->json($subproject->load('gozars')->load('districts.province'), 201);
    }


    public function removeGozar(Request $request) {
        $subproject = Subproject::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        $subproject->gozars()->detach($request->gozar_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }

    public function removeDistrict(Request $request) {
        $subproject = Subproject::find($request->id);
        $district = District::find($request->district_id);
        foreach ($subproject->gozars as $key => $value) {
            if ($value->district_id === $request->district_id ) {
                $subproject->gozars()->detach($value->id);
            }
        }

        $subproject->districts()->detach($request->district_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }




}
