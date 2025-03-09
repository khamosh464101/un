<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [ 'name' => 'view role'],
            [ 'name' => 'create role'],
            [ 'name' => 'edit role'],
            [ 'name' => 'delete role'],
            [ 'name' => 'locaton view'],
            [ 'name' => 'locaton create'],
            [ 'name' => 'locaton update'],
            [ 'name' => 'locaton delete'],
            [ 'name' => 'program status view'],
            [ 'name' => 'program status create'],
            [ 'name' => 'program status update'],
            [ 'name' => 'program status delete'],
            [ 'name' => 'project status view'],
            [ 'name' => 'project status create'],
            [ 'name' => 'project status update'],
            [ 'name' => 'project status delete'],
            [ 'name' => 'staff status view'],
            [ 'name' => 'staff status create'],
            [ 'name' => 'staff status update'],
            [ 'name' => 'staff status delete'],
            [ 'name' => 'activity type view'],
            [ 'name' => 'activity type create'],
            [ 'name' => 'activity type update'],
            [ 'name' => 'activity type delete'],
            [ 'name' => 'activity status view'],
            [ 'name' => 'activity status create'],
            [ 'name' => 'activity status update'],
            [ 'name' => 'activity status delete'],
            [ 'name' => 'ticket status view'],
            [ 'name' => 'ticket status create'],
            [ 'name' => 'ticket status update'],
            [ 'name' => 'ticket status delete'],
            [ 'name' => 'ticket type view'],
            [ 'name' => 'ticket type create'],
            [ 'name' => 'ticket type update'],
            [ 'name' => 'ticket type delete'],
            [ 'name' => 'ticket priority view'],
            [ 'name' => 'ticket priority create'],
            [ 'name' => 'ticket priority update'],
            [ 'name' => 'ticket priority delete'],
            [ 'name' => 'program view'],
            [ 'name' => 'program create'],
            [ 'name' => 'program update'],
            [ 'name' => 'program delete'],
            [ 'name' => 'donor view'],
            [ 'name' => 'donor create'],
            [ 'name' => 'donor update'],
            [ 'name' => 'donor delete'],
            [ 'name' => 'project view'],
            [ 'name' => 'project create'],
            [ 'name' => 'project update'],
            [ 'name' => 'project delete'],
            [ 'name' => 'staff view'],
            [ 'name' => 'staff create'],
            [ 'name' => 'staff update'],
            [ 'name' => 'staff delete'],
        ];

        $user = User::first();
        $role = Role::create(["name" => 'Super admin', "guard_name" => "web"]);
        foreach ($permissions as $key => $value) {
            $p = Permission::create($value);
            $role->givePermissionTo($p);
        }
        $user->assignRole($role);
    }
}
