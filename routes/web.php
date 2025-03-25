<?php

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\TestController;


Route::get('/test', [TestController::class, 'index']);
Route::get('/', function () {
    // $role = Role::create(['name' => 'writer']);
    // $permission = Permission::create(['name' => 'edit articles']);
    // return $role;
    $filePath = storage_path('app/data/villages.json');
    $jsonData = file_get_contents($filePath);
    $dataArray = json_decode($jsonData, true);
    // foreach($dataArray as $key => $p) {
    //     echo $key;
    //     echo $p['name']. '<br/>';
    // }

     dd(count($dataArray));
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';
