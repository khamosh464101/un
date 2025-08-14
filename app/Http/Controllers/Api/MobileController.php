<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Http\Controllers\ProgramController;
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
        $ticket->documents;
        $ticket->owner;
        $ticket->responsible;
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
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProgramController::getFileName($staff->name, $request->file('photo')));
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

}
