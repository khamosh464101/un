<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Staff;
use Modules\Projects\Http\Requests\StaffRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffController
{
    public function select2() {
        return response()->json(Staff::select('id', 'name')->get(), 201);
    }
    public function index(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'name';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $staffs = Staff::with('status')->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->orderBy($field, $sortType)->paginate(8);
        return response()->json($staffs, 201);
    }
    
    public function store(StaffRequest $request) {
        $data = $request->safe()->except(['photo']);
        // Handle the file upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProgramController::getFileName($data['name'], $request->file('photo')));
            $data['photo'] = $get_file;
        }


        $staff = Staff::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $staff = Staff::find($id);
        $staff->status;
        $staff->logs;
        $staff->documents;
        return response()->json($staff, 201);
    }

    public function update(StaffRequest $request, $id) {
        $data = $request->safe()->except(['photo', '_method']);
        // Handle the file upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProgramController::getFileName($data['name'], $request->file('photo')));
            $data['photo'] = $get_file;
        }
        $staff = Staff::find($id);
        $staff->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $staff], 201);
    }

    public function destroy($id) {
       
        $staff = Staff::find($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        return $staff;

        if ($staff->activities->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this staff because it has associated activities.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        foreach ($staff->documents as $key => $document) {
            $document->delete();
        }
    
        $staff->delete();
        return response()->json(['message' => 'Staff deleted successfully'], 201);
    }
}
