<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;

Route::middleware(['auth:sanctum', 'twofactor'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('verify/resend', [TwoFactorController::class, 'resend'])->name('verify.resend');
    Route::resource('verify', TwoFactorController::class)->only(['store']);

    Route::post('/roles', [RoleController::class, 'index']);
    Route::post('/role/create', [RoleController::class, 'create']);
    Route::get('/role/{id}', [RoleController::class, 'get']);
    Route::post('/role/update', [RoleController::class, 'update']);
    Route::delete('/role/{id}/delete', [RoleController::class, 'destroy']);
    Route::get('/permissions', [PermissionController::class, 'index']);
});
