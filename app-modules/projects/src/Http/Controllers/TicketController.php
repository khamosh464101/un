<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\TicketRequest;
use Carbon\Carbon;

class TicketController
{
    public function index(Request $request) {
        $data = TicketStatus::with([
            'tickets' => function ($query) use ($request) {
                $query->where('activity_id', $request->activity_id)->orderBy('order', 'asc')
                      ->with(['type', 'priority', 'responsible']);
            }
        ])->get();
        return response()->json($data, 201);

    }

    public function store(TicketRequest $request) {
        $data = $request->validated();
        $ticket = Ticket::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $ticket], 201);
    }

    public function reorder(Request $request) {
        $this->setOrder($request->items);
        return response()->json(['message' => 'Successfully reordered!'], 201);
    }

    private function setOrder($items) {
        foreach($items as $key => $item) {
            $ticket = Ticket::find($item['id']);
            $ticket->order = ++$key;
            $ticket->save();
        }
    }

    public function move(Request $request) {
        $this->setOrder($request->sItems);
        foreach($request->dItems as $key => $item) {
            $ticket = Ticket::find($item['id']);
            $ticket->order = ++$key;
            $ticket->ticket_status_id = $item['ticket_status_id'];
            $ticket->save();
        }
        return response()->json(['message' => 'Successfully moved!'], 201);
    }
}
