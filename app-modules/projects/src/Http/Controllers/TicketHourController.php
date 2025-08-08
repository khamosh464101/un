<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\TicketHour;

use Auth;

class TicketHourController
{
    public function store(Request $request) {
   
        $data = $request->validate(['title' => 'required', 'value' => 'required', 'ticket_id' => 'required', 'comment' => 'nullable']);
        $data['user_id'] = Auth::user()->id;
        $log = TicketHour::create($data);
        $log->user;
        return response()->json(['message' => 'Log time saved', 'data' => $log], 201);
    }

    public function update(Request $request, $id) {
        $data = $request->validate(['title' => 'required', 'value' => 'required', 'ticket_id' => 'required', 'comment' => 'nullable']);
        $log = TicketHour::find($id);
        if (Auth::user()->id !==  $log->user_id) {
            return response()->json(['error' => 'Not authorized!'], 410);
        }
        $log->update($data);
        $log->user;
        return response()->json(['message' => 'Log time updated', 'data' => $log], 201);
    }

    public function destroy($id) {
        $log = TicketHour::find($id);
        if (Auth::user()->id !==  $log->user_id) {
            return response()->json(['error' => 'Not authorized!'], 410);
        }
        $log->delete();
        return response()->json(['message' => 'Log time deleted'], 201);
    }
}
