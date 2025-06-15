<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Partner;
use Modules\Projects\Http\Requests\PartnerRequest;
use Illuminate\Support\Facades\Gate;

class PartnerController
{
    public function select2() {
       
        return response()->json(Partner::select('id', 'business_name')->get(), 201);
    }

    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'business_name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $partners = Partner::withCount('subprojects')->when($search, function($query) use ($search) {
            $query->where('business_name', 'like', '%'.$search.'%')
                ->orWhere('representative_name', 'like', '%'.$search.'%')
                ->orWhere('representative_phone1', 'like', '%'.$search.'%')
                ->orWhere('website', 'like', '%'.$search.'%')
                ->orWhere('representative_email', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($partners, 201);
    }

    public function store(PartnerRequest $request) {
        Gate::authorize('create', Partner::class);
        $data = $request->validated();

        $partner = Partner::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $partner], 201);
    }

    public function edit($id) {
        $partner = Partner::with('logs.causer')->find($id);
        $partner->subprojects;
        $partner->documents;
        return response()->json($partner, 201);
    }

    public function update(PartnerRequest $request, $id) {
        $partner = Partner::find($id);
        Gate::authorize('update', $partner);
        $data = $request->validated();
        
        $partner->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $partner], 201);
    }

    public function destroy($id) {
       
        $partner = Partner::find($id);
        Gate::authorize('delete', $partner);
        if (!$partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        if ($partner->subprojects->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this partner because it has associated Sub Projects.'
            ], 400);  // Return a 400 Bad Request status
        }
        $partner->delete();
        return response()->json(['message' => 'Partner deleted successfully'], 201);
    }
}
