<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

class NotificationController extends Controller
{
    public function getUnread() {
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $notifications = Auth::user()->unreadNotifications()->latest()->get();
        return response()->json(['total_unread' => $unreadCount, 'data' => $notifications], 201);
    }

    public function markAsRead($id) {
        $user = Auth::user();
        $notification = $user->notifications->find($id);
        $notification->markAsRead();
        return response()->json(['message' => 'Marked as Read!'], 201);
    }
}
