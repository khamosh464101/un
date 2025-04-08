<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;

Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::resource('user', UserController::class)->only(['store']);
    
    Route::get('/notifications/mark-as-read/{id}/{type}', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('verify/resend', [TwoFactorController::class, 'resend'])->name('verify.resend');
    Route::resource('verify', TwoFactorController::class)->only(['store']);
    Route::post('phoneVerify', [TwoFactorController::class, 'phoneVerify']);
    Route::get('roles/select2', [RoleController::class, 'select2']);
    Route::post('/roles', [RoleController::class, 'index']);
    Route::post('/role/create', [RoleController::class, 'create']);
    Route::get('/role/{id}', [RoleController::class, 'get']);
    Route::post('/role/update', [RoleController::class, 'update']);
    Route::delete('/role/{id}/delete', [RoleController::class, 'destroy']);
    Route::get('/permissions', [PermissionController::class, 'index']);
});
