<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\TicketType;
use Modules\Projects\Http\Requests\TicketTypeRequest;

class TicketTypeController
{
    public function select2() {
        return response()->json(TicketType::all(), 201);
    }

    public function index(Request $request) {
        $search = $request->search;
        $statuses = TicketType::withCount('tickets')->when($search, function($query) use ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->paginate(8);
        return response()->json($statuses, 201);
    }

    public function store(TicketTypeRequest $request) {
        $data = $request->validated();
         $status = TicketType::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $data], 201);
    }

    public function edit($id) {
        $status = TicketType::find($id);
        return response()->json($status, 201);
    }

    public function update(TicketTypeRequest $request, $id) {
        $data = $request->safe()->except(['_method']);
        $status = TicketType::find($id);
        $status->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $status], 201);
    }

    public function destroy($id) {
        $status = TicketType::find($id);
        if (!$status) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($status->tickets->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this Record because it has associated with other records.'
            ], 400);  // Return a 400 Bad Request status
        }
        
        $status->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }
}
