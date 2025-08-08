<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\TicketComment;
use Illuminate\Support\Facades\Gate;

use Auth;
class TicketCommentController
{
    public function store(Request $request) {
        $data = $request->validate(['content' => 'required', 'ticket_id' => 'required']);
        $data['user_id'] = Auth::user()->id;
        $comment = TicketComment::create($data);
        $comment->user;
        return response()->json(['message' => 'Comment saved', 'data' => $comment], 201);
    }

    public function update(Request $request, $id) {
        $data = $request->validate(['content' => 'required', 'ticket_id' => 'required']);
        $comment = TicketComment::find($id);
        if (Auth::user()->id !==  $comment->user_id) {
            return response()->json(['error' => 'Not authorized!'], 410);
        }
        $comment->update($data);
        $comment->user;
        return response()->json(['message' => 'Comment updated', 'data' => $comment], 201);
    }

    public function destroy($id) {
        $comment = TicketComment::find($id);
        if (Auth::user()->id !==  $comment->user_id) {
            return response()->json(['error' => 'Not authorized!'], 410);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted'], 201);
    }
}
