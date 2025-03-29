<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Modules\Projects\Models\Staff;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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
        $user->assignRole($role);
        $user->roles;
        return response()->json(['message' => 'Sucessfully'. $request->id ? 'updated!' : 'added!', 'data' => $user], 201);
    }
}
