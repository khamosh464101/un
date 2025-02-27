<?php

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

Route::get('/', function () {
    $role = Role::create(['name' => 'writer']);
    $permission = Permission::create(['name' => 'edit articles']);
    return $role;
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';
