<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

class TestController extends Controller
{

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deviceToken = 'ffZIOXzY8tLbn-uZu7LXlt:APA91bHUA3p1BvIFqrCJXdgKkP-ujtC0Uxz5Hyy6ihbgNbp8kCtJHWkdO_22PAmMNUrKkDB3cEHEgGdYN8iz6UbuWI5u-8smnWQtFNjxwZrXakQEMCUVSQ8';
        $notification = Notification::fromArray([
            'title' => 'Laravel notificaton',
            'body' => 'Laravel notification content',
            'click_action' => 'https://caedo.org',
            'icon' => 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png',
            'image' => 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png',
        ]);

        // $config = WebPushConfig::fromArray([
        //     'notification' => [
        //         'title' => '$GOOG up 1.43% on the day',
        //         'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
        //         'icon' => 'https://my-server.example/icon.png',
        //     ],
        //     'fcm_options' => [
        //         'link' => 'https://my-server.example/some-page',
        //     ],
        // ]);
        
        // $message = $message->withWebPushConfig($config);
        // $config = WebPushConfig::fromArray([
        //     'fcm_options' => [
        //         'link' => 'https://caedo.org',
        //     ],
        // ]);

        $message = CloudMessage::new()
             ->withNotification($notification) // optional
            ->withData([]) // optional
            ->toToken($deviceToken);

        // $message = $message->withWebPushConfig($config);

        $result = $this->messaging->send($message);
        return $result;
                // Validate credentials (e.g., email and password)


            // // Send OTP to user's registered phone number after successful login
            // $phoneNumber = '+93747566686';  // Assuming phone number is saved in your user table
            // $defaultAuth = Firebase::auth();
            // // Initialize Firebase phone authentication
            // try {
            //     // Request OTP via Firebase Authentication

            //     $defaultAuth->sendOtpToPhone($phoneNumber);
            //     return response()->json(['message' => 'OTP sent successfully']);
            // } catch (\Exception $e) {
            //     return response()->json(['error' => 'Failed to send OTP'], 500);
            // }
      
//         $factory = (new Factory)
//     ->withServiceAccount('firebase-auth.json')
//     ->withDatabaseUri('https://my-project-default-rtdb.firebaseio.com');

// $auth = $factory->createAuth();
// $realtimeDatabase = $factory->createDatabase();
// $cloudMessaging = $factory->createMessaging();
// $remoteConfig = $factory->createRemoteConfig();
// $cloudStorage = $factory->createStorage();
// $firestore = $factory->createFirestore();
        // $defaultAuth = Firebase::auth();
        // // Return an instance of the Auth component for a specific Firebase project
        // $appAuth = Firebase::project('app')->auth();
        // $anotherAppAuth = Firebase::project('another-app')->auth();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
