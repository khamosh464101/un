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
            
            ['name' => 'view dashboard'],
            [ 'name' => 'view role'],
            [ 'name' => 'create role'],
            [ 'name' => 'edit role'],
            [ 'name' => 'delete role'],
            [ 'name' => 'location view'],
            [ 'name' => 'location create'],
            ['name' => 'view setting'],
            ['name' => 'manage backup'],

            [ 'name' => 'location update'],
            [ 'name' => 'location delete'],
            [ 'name' => 'project status view'],
            [ 'name' => 'project status create'],
            [ 'name' => 'project status update'],
            [ 'name' => 'project status delete'],
            [ 'name' => 'subproject type view'],
            [ 'name' => 'subproject type create'],
            [ 'name' => 'subproject type update'],
            [ 'name' => 'subproject type delete'],
            [ 'name' => 'staff status view'],
            [ 'name' => 'staff status create'],
            [ 'name' => 'staff status update'],
            [ 'name' => 'staff status delete'],
            [ 'name' => 'staff contract type view'],
            [ 'name' => 'staff contract type create'],
            [ 'name' => 'staff contract type update'],
            [ 'name' => 'staff contract type delete'],
            [ 'name' => 'activity type view'],
            [ 'name' => 'activity type create'],
            [ 'name' => 'activity type update'],
            [ 'name' => 'activity type delete'],
            [ 'name' => 'activity status view'],
            [ 'name' => 'activity status create'],
            [ 'name' => 'activity status update'],
            [ 'name' => 'activity status delete'],
            [ 'name' => 'task status view'],
            [ 'name' => 'task status create'],
            [ 'name' => 'task status update'],
            [ 'name' => 'task status delete'],
            [ 'name' => 'task priority view'],
            [ 'name' => 'task priority create'],
            [ 'name' => 'task priority update'],
            [ 'name' => 'task priority delete'],
            [ 'name' => 'donor view'],
            [ 'name' => 'donor create'],
            [ 'name' => 'donor update'],
            [ 'name' => 'donor delete'],
            [ 'name' => 'project view'],
            [ 'name' => 'project create'],
            [ 'name' => 'project update'],
            [ 'name' => 'project delete'],
            [ 'name' => 'partner view'],
            [ 'name' => 'partner create'],
            [ 'name' => 'partner update'],
            [ 'name' => 'partner delete'],
            [ 'name' => 'subproject view'],
            [ 'name' => 'subproject create'],
            [ 'name' => 'subproject update'],
            [ 'name' => 'subproject delete'],
            [ 'name' => 'staff view'],
            [ 'name' => 'staff create'],
            [ 'name' => 'staff update'],
            [ 'name' => 'staff delete'],
            [ 'name' => 'activity view'],
            [ 'name' => 'activity create'],
            [ 'name' => 'activity update'],
            [ 'name' => 'activity delete'],
            [ 'name' => 'task view'],
            [ 'name' => 'task create'],
            [ 'name' => 'task update'],
            [ 'name' => 'task delete'],
            [ 'name' => 'profile view'],
            [ 'name' => 'profile create'],
            [ 'name' => 'profile update'],
            [ 'name' => 'profile delete'],
            [ 'name' => 'add profile as beneficiary'],
            [ 'name' => 'remove profile as beneficairy'],
            [ 'name' => 'manage excel'],
            [ 'name' => 'move profile to archive'],
            [ 'name' => 'download profile'],
            [ 'name' => 'archive view'],
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
