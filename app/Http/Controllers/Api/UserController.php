<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Ticket;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Http\Requests\UpdateProfileRequest;
use Modules\Projects\Http\Controllers\ProjectController;
use Auth;

class UserController extends Controller
{
    public function profile() {
        $staff = Auth::user()->staff;
        $staff->user;
        $staff->status;
        return response()->json($staff, 201);

    }

    public function myTickets(Request $request) {
       return $data = TicketStatus::with([
            'tickets' => function ($query) use ($request) {
                $query->where('activity_id', $request->activity_id)
                ->where('responsible_id', Auth::user()->staff_id)
                ->orderBy('order1', 'asc')
                ->with(['priority', 'responsible']);
            }
        ])->get();
        return response()->json(count($data->tickets) > 0 ? $data : [], 201);
    }


    public function reorder(Request $request) {
        $this->setOrder($request->items);
        return response()->json(['message' => 'Successfully reordered!'], 201);
    }

    private function setOrder($items) {
        foreach($items as $key => $item) {
            $ticket = Ticket::find($item['id']);
            $ticket->order1 = ++$key;
            $ticket->save();
        }
    }

    public function move(Request $request) {
        $this->setOrder($request->sItems);
        foreach($request->dItems as $key => $item) {
            $ticket = Ticket::find($item['id']);
            $ticket->order1 = ++$key;
            $maxOrder = $ticket->activity->tickets()
                ->where('ticket_status_id', $item['ticket_status_id'])
                ->max('order');

            $ticket->order = ($maxOrder ?? 0) + 1;
            $ticket->ticket_status_id = $item['ticket_status_id'];
            $ticket->save();
        }
        return response()->json(['message' => 'Successfully moved!'], 201);
    }
    

    public function updateProfile(UpdateProfileRequest $request) {

        $data = $request->safe()->except(['photo']);
        // Handle the file upload
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            
            $get_file = $request->file('photo')->storeAs('project-management/staff/photo', ProjectController::getFileName($data['name'], $request->file('photo')));
            $data['photo'] = $get_file;
        }
        $user = Auth::user();
        $staff = $user->staff;
        $staff->update($data);
        $user->update([
            'name' => $staff->name,
            'phone' => $staff->phone1,
        ]);
        if ($request->password) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        return response()->json(['message' => 'Sucessfully updated!', 'data' => $staff], 201);
    }
    public function store(Request $request) {

        $staff = Staff::find($request->staff_id);
        $user;
        if ($request->id) {
            $user = User::find($request->id);
            if ($request->password) {
                $user->password = bcrypt($request->password);
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $staff->name,
                'email' => $staff->official_email,
                'phone' => $staff->phone1,
                'password' => bcrypt($request->password),
                'staff_id' => $staff->id,
            ]);
        }
        $role = Role::find($request->role_id);
        $user->roles()->detach();
        $user->assignRole($role);
        $user->roles;
        return response()->json(['message' => 'Sucessfully'. $request->id ? 'updated!' : 'added!', 'data' => $user], 201);
    }
}
