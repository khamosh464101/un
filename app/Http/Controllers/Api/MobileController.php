<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Project;
use Modules\Projects\Http\Controllers\ProjectController;
use Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MobileController extends Controller
{
    public function myTickets(Request $request) {
       return $data = TicketStatus::with([
            'tickets' => function ($query) use ($request) {
                $query->where('responsible_id', Auth::user()->staff_id)
                ->orderBy('order1', 'asc')
                ->with(['priority', 'owner', 'comments.user']);
            }
        ])->get();
        return response()->json(count($data->tickets) > 0 ? $data : [], 201);
    }

    public function ticket($id) {
        $ticket = Ticket::with('comments.user')->find($id);
        $ticket->status;
        $ticket->priority;
        $ticket->owner;
        $ticket->responsible;
        $documents = $ticket->documents->map(function ($doc) {
            $doc->url = asset('storage/' . $doc->path);
            return $doc;
        });
        $ticket->setRelation('documents', $documents);
        return response()->json($ticket, 201);
    }

    public function ticketStatuses(Request $request) {
        $data = TicketStatus::all();
        return response()->json($data, 201);
    }

    public function changeTicketStatus(Request $request) {
        $ticket = Ticket::find($request->id);

        
        if ($ticket->status->id === 4) {
            return response()->json(['status' => 403], 201);
        }
        $ticket->update(['ticket_status_id' => $request->status_id]);
        $ticket = Ticket::find($request->id);
        $ticket->status;
        return response()->json($ticket, 201);

    }

    public function getNotifications() {
        $notifications = Auth::user()->notifications()->orderBy('created_at', 'desc')->get();

        return response()->json($notifications, 201);
    }

    public function markAsRead($id, $type) {
        $user = Auth::user();
        $notification;
        if ($type == 'pk') {
            $notification = $user->notifications->find($id);
        } else {
            $notification = $user->notifications()->whereJsonContains('data->uuid', $id)->first();
        }
        
            // Check if the notification was found
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['notification' => $notification ,'message' => 'Marked as Read!'], 200); // Changed status code to 200 (OK)
        }

        return response()->json(['message' => 'Notification not found!'], 404);
    }

    public function delete($id, $type) {
        $user = Auth::user();
        $notification;
        if ($type == 'pk') {
            $notification = $user->notifications->find($id);
        } else {
            $notification = $user->notifications()->whereJsonContains('data->uuid', $id)->first();
        }
        
            // Check if the notification was found
        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted successfully']); // Changed status code to 200 (OK)
        }

        return response()->json(['message' => 'Notification not found!'], 404);
    }



   public function deleteAll()
    {
        auth()->user()->notifications()->delete();
        return response()->json(['message' => 'All notifications deleted successfully']);
    }

    public function updateUser(Request $request) {
        $user = Auth::user();
        $staff = $user->staff;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProjectController::getFileName($staff->name, $request->file('photo')));
            $staff->update(['photo' => $get_file]);
            return response()->json($staff->photo);
        }
        
        $staff->update([
            $request->field => $request->value
        ]);

        if (in_array($request->field, ['name', 'phone1'])) {
            $user->update([
                $request->field => $request->value
            ]);
        }

        return response()->json($user);
    }

    public function changePassword(Request $request)
{
    $request->validate([
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
        ],
    ]);

    // // Verify current password
    // if (!Hash::check($request->password, auth()->user()->password)) {
    //     return response()->json([
    //         'message' => 'Current password is incorrect'
    //     ], 422);
    // }

    // Update password
    auth()->user()->update([
        'password' => bcrypt($request->password)
    ]);

    return response()->json([
        'message' => 'Password changed successfully'
    ]);

    
    }

     public function dashboard(Request $request) {
       $statuses = TicketStatus::withCount([
            'tickets as tickets_count' => function ($query) use ($request) {
                $query->where('responsible_id', Auth::user()->staff_id);
            }
        ])->get();

        $staff = Auth::user()->staff;

        return response()->json([
            'statuses' => $statuses, 
            'submissions' => Auth::user()->submissions->count(),
            'projects' => $staff->tickets()
                        ->whereHas('activity', function ($query) {
                            $query->whereNotNull('project_id');
                        })
                        ->with('activity:id,project_id') // optimize select
                        ->get()
                        ->pluck('activity.project_id')
                        ->unique()
                        ->count()
        ], 201);
    }

    public function getSubmissions() {
        return response()->json(Auth::user()->submissions->where('submission_status_id', 3));
    }


    public function ownedTickets(Request $request) {
        return TicketStatus::with([
            'tickets' => function ($query) {
                $query->where('owner_id', Auth::id())
                    ->orderBy('order1', 'asc')
                    ->with(['priority', 'owner', 'responsible', 'comments.user', 'activity:id,project_id']);
            }
        ])->get();
    }

    public function ownedTicketDetails($id) {
        $ticket = Ticket::with([
            'comments.user',
            'status',
            'priority',
            'documents',
            'owner',
            'responsible',
        ])->findOrFail($id);
        return response()->json($ticket, 200);
    }


    public function getFormData(Request $request) {
        $staffId    = Auth::user()->staff_id;
        $priorities = \Modules\Projects\Models\TicketPriority::all(['id', 'title']);
        $statuses   = \Modules\Projects\Models\TicketStatus::all(['id', 'title']);
        $staff      = \Modules\Projects\Models\Staff::all(['id', 'name', 'photo']);
        $projects   = \Modules\Projects\Models\Project::with(['activities' => function($q) {
            $q->select('id', 'title', 'activity_number', 'project_id');
        }])->where('manager_id', $staffId)->get(['id', 'title', 'code', 'manager_id']);
        $isManager  = $projects->isNotEmpty();
        return response()->json(compact('priorities', 'statuses', 'staff', 'projects', 'isManager'));
    }

    public function createTicket(Request $request) {
        $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'start_date'          => 'required|date',
            'deadline'            => 'required|date|after_or_equal:start_date',
            'responsible_id'      => 'required|exists:staff,id',
            'ticket_priority_id'  => 'required|exists:ticket_priorities,id',
            'ticket_status_id'    => 'required|exists:ticket_statuses,id',
            'activity_id'         => 'required|exists:activities,id',
        ]);

        $ticket = \Modules\Projects\Models\Ticket::create([
            'title'              => $request->title,
            'description'        => $request->description,
            'start_date'         => $request->start_date,
            'deadline'           => $request->deadline,
            'responsible_id'     => $request->responsible_id,
            'ticket_priority_id' => $request->ticket_priority_id,
            'ticket_status_id'   => $request->ticket_status_id,
            'activity_id'        => $request->activity_id,
            'owner_id'           => Auth::id(),
        ]);

        return response()->json(
            \Modules\Projects\Models\Ticket::with(['priority', 'status', 'owner', 'responsible'])->find($ticket->id),
            201
        );
    }

    public function updateTicket(Request $request, $id) {
        $ticket = \Modules\Projects\Models\Ticket::findOrFail($id);

        if ($ticket->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'start_date'          => 'required|date',
            'deadline'            => 'required|date|after_or_equal:start_date',
            'responsible_id'      => 'required|exists:staff,id',
            'ticket_priority_id'  => 'required|exists:ticket_priorities,id',
            'ticket_status_id'    => 'required|exists:ticket_statuses,id',
            'activity_id'         => 'required|exists:activities,id',
        ]);

        $ticket->update($request->only([
            'title', 'description', 'start_date', 'deadline',
            'responsible_id', 'ticket_priority_id', 'ticket_status_id', 'activity_id',
        ]));

        return response()->json(
            \Modules\Projects\Models\Ticket::with(['priority', 'status', 'owner', 'responsible'])->find($ticket->id)
        );
    }

    public function deleteTicket($id) {
        $ticket = \Modules\Projects\Models\Ticket::findOrFail($id);

        if ($ticket->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }


    public function uploadAttachment(Request $request) {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'title'     => 'required|string|max:255',
            'file'      => 'required|file|max:20480',
        ]);

        $ticket = Ticket::findOrFail($request->ticket_id);
        $file   = $request->file('file');
        $ext    = $file->getClientOriginalExtension();
        $slug   = \Illuminate\Support\Str::slug($request->title);
        $name   = $slug . '-' . now()->format('Y-m-d-H-i-s') . '.' . $ext;
        $path   = $file->storeAs('project-management/document', $name, 'public');

        $document = $ticket->documents()->create([
            'title'       => $request->title,
            'path'        => $path,
            'size'        => round($file->getSize() / 1048576, 2),
            'description' => null,
        ]);

        $document->url = asset('storage/' . $path);
        return response()->json($document, 201);
    }

    public function deleteAttachment($id) {
        $document = \Modules\Projects\Models\Document::findOrFail($id);
        $document->delete();
        return response()->json(['message' => 'Attachment deleted successfully']);
    }

}
