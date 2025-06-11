<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\SettingController;

Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::resource('/user', UserController::class)->only(['store']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/tickets', [UserController::class, 'myTickets']);
    Route::post('/user/tickets/move', [UserController::class, 'move']);
    Route::post('/user/tickets/reorder', [UserController::class, 'reorder']);
    Route::post('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/mark-as-read/{id}/{type}', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings/store', [SettingController::class, 'store']);
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
