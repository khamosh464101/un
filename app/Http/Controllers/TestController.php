<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

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
        $deviceToken = 'e8QrzP6AOpr7Xe5L-K8y7E:APA91bFtgpbgSjGqJ7Fuf7PIdVwwjywJagfCQMvlCagickWYJL-ycVQbrX_r6iCcOR3FclALyiF5whyKiG7HJruSPBOsQ-OmWsYH_8AwgCTcV36820cYEhs';
        $notification = Notification::fromArray([
            'title' => 'Laravel notificaton',
            'body' => 'Laravel notification content',
            'image' => 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png',
        ]);

        $message = CloudMessage::new()
            ->withNotification($notification) // optional
            ->withData([]) // optional
            ->toToken($deviceToken);

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
