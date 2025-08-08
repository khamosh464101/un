<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\Role\RolesResource;
use App\Http\Resources\Role\RoleResource;
use Illuminate\Support\Facades\DB;
use Auth;

class RoleController extends Controller
{
    public function select2() {
        return response()->json(Role::select('id as value', 'name as label')->get(), 201);
    }
    public function index(Request $request) {
        $search = $request->search;
        $roles = Role::when($search, function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->paginate(8);
        return RolesResource::collection($roles); 
    }

    public function get($id) {
        return new RoleResource(Role::find($id)); 
    }

    public function create (Request $request) {
         try {
            $role = [];
            DB::transaction(function () use ($request, &$role)  {
                $role = Role::create(["name" => $request->name, "guard_name" => "web"]);

                foreach ($request->assignedPermissions as $key => $value) {
                    
                    $permission = Permission::find($value);
            
                    $role->givePermissionTo($permission);
                }
                
            });

            return response()->json($role, 201);
            
         } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        
    }

    public function update(Request $request) {

        try {
            DB::transaction(function () use ($request)  {
                $role = Role::find($request->role["id"]);
                $role->update(["name" => $request->role["name"]]);
                foreach ($request->permissions as $key => $value) {
                    $permission = Permission::find($value["id"]);
                    if ($value["checked"] && !$role->hasPermissionTo($permission->name)) {
                        $role->givePermissionTo($permission->name);
                    } elseif (!$value["checked"] && $role->hasPermissionTo($permission->name)) {
                        $role->revokePermissionTo($permission->name);
                    }
                }
            });
            return response()->json(['permissions' => Auth::user()->getAllPermissions()->pluck('description', 'name')->toArray()], 201);
            
         } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function destroy($id) {

        $role = Role::find($id);

        if ($role->users->count() > 0) {
            return response()->json(['message' => "This role has been assciated with a user, you can't delete it!"], 500);
        }

        try {
            $permissions = $role->permissions;
            foreach ($permissions as $key => $value) {
                $role->revokePermissionTo($value->name);
            }

            $role->delete();
            return response()->json(['message' => "Successfully deleted!"], 201);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 500);
       }
        
    }
}
