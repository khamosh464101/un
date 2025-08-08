<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotificationController extends Controller
{
    
    public function index() {
    
        $notifications = Auth::user()->notifications()->paginate(10);
        return response()->json($notifications, 201);
    }
    public function getUnread() {
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $notifications = Auth::user()->unreadNotifications()->latest()->get();
        return response()->json(['total_unread' => $unreadCount, 'data' => $notifications], 201);
    }

    public function markAsRead($id, $type) {
        $user = Auth::user();
        $notification;
        if ($type == 'pk') {
            $notification = $user->notifications->find($id);
        } else {
            $notification = $user->notifications()->whereJsonContains('data->uuid', $id)->first();
        }
        
            // Check if the notification was found
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['notification' => $notification ,'message' => 'Marked as Read!'], 200); // Changed status code to 200 (OK)
        }

        return response()->json(['message' => 'Notification not found!'], 404);
    }
}
