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

    public function edit($id) {
        $ticket = Ticket::with('gozars.district.province')->with('comments.user')->with('hours.user')->withSum('hours', 'value')->find($id);
        $ticket->status;
        $ticket->type;
        $ticket->priority;
        $ticket->logs;
        $ticket->documents;
        $ticket->activity->project->program;
        $ticket->owner;
        $ticket->responsible;
        return response()->json($ticket, 201);
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

    public function getLocation($id) {
        $gozars = Gozar::select('id as value', 'name as label', 'district_id')->whereHas('activities', function ($query) use ($id) {
            $query->where('activities.id', $id);
        })->get();
        
        // Get unique Districts from these Villages
        $districts = District::select('id as value', 'name as label', 'province_id')->whereIn('id', $gozars->pluck('district_id')->unique())->get();
        
        // Get unique Provinces from these Districts
        $provinces = Province::select('id as value', 'name as label')->whereIn('id', $districts->pluck('province_id')->unique())->get();
        
        return response()->json([
            'gozars' => $gozars,
            'districts' => $districts,
            'provinces' => $provinces
        ], 201);
        
    }

    public function addGozar(Request $request) {
        $ticket = Ticket::find($request->id);
        $gozar = Gozar::find($request->gozar_id);
        if ($ticket->gozars->contains($gozar)) {
            return response()->json(['message' => 'Already exist'], 500);
        }
       $ticket->gozars()->attach($gozar);
        $gozar->district->province;
        return response()->json($gozar, 201);

    }

    public function removeGozar(Request $request) {
      
        $ticket = Ticket::find($request->id);
        $ticket->gozars()->detach($request->gozar_id);
        return response()->json(['message' => 'Successfully removed!'], 201);

    }
}
