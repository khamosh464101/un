<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\TicketRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class TicketController
{
    public function select2($id = null) {
        $tickets;
        if ($id) {
           $activity = Activity::find($id);
           $tickets = $activity->tickets;

        } else {
            $tickets = Ticket::select('id', 'title')->get();
        }
        return response()->json($tickets, 201);
  
    }
    public function list(Request $request) {
 
        $search = $request->search;
        $sortBy = $request->sortBy;
        $priorityId = $request->priorityId;
        $statusId = $request->statusId;
        $projectId = $request->projectId;
        $activityId = $request->activityId;
        $field = ($sortBy == 'Oldest' || $sortBy == 'Newest') ? 'id' : 'title';
        $sortType = ($sortBy == 'Z - A' || $sortBy == 'Newest') ? 'DESC' : 'ASC';
        $tickets = Ticket::with('status')
                    ->with('priority')
                    ->with('responsible')
                    ->with('activity')
                    ->when($search, function($query) use ($search) {
                        $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('ticket_number', $search);
                    })
                    ->when($statusId, function($query) use ($statusId) {
                        $query->where('ticket_status_id', $statusId);
                    })
                 
                    ->when($priorityId, function($query) use ($priorityId) {
                        $query->where('ticket_priority_id', $priorityId);
                    })
                    ->when($projectId, function($query) use ($projectId) {
                        $query->whereHas('activity', function ($q) use ($projectId) {
                            $q->where('project_id', $projectId);
                        });
                    })
                    ->when($activityId, function($query) use ($activityId) {
                        $query->where('activity_id', $activityId);
                    })
                    ->orderBy($field, $sortType)
                    ->paginate(8);
        return response()->json($tickets, 201);
    }

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
        Gate::authorize('create', Ticket::class);
        $data = $request->validated();
        $ticket = Ticket::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $ticket], 201);
    }



    public function edit($id) {
        $ticket = Ticket::with('gozars.district.province')->with('comments.user')->with('hours.user')->withSum('hours', 'value')->with('logs.causer')->find($id);
        $ticket->status;
        $ticket->type;
        $ticket->priority;
        $ticket->logs;
        $ticket->documents;
        $ticket->activity->project;
        $ticket->owner;
        $ticket->responsible;
        $ticket->parent;
        $ticket->children;
        return response()->json($ticket, 201);
    }

    public function update(TicketRequest $request, $id) {
        $ticket = Ticket::find($id);
        Gate::authorize('update', $ticket);
        $data = $request->validated();
        
        $ticket->update($data);
        return response()->json(['message' => 'Sucessfully updated!', 'data' => $ticket], 201);
    }

    public function destroy($id) {
        $ticket = Ticket::find($id);
        Gate::authorize('delete', $ticket);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted successfully'], 201);
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
            $maxOrder = $ticket->activity->tickets()
                ->where('ticket_status_id', $item['ticket_status_id'])
                ->where('responsible_id', $ticket->responsible_id)
                ->max('order1');

            $ticket->order1 = ($maxOrder ?? 0) + 1;
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
