<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Notifications\SendTwoFactorCode;
use Illuminate\Http\JsonResponse;
use Kreait\Laravel\Firebase\Facades\Firebase;

class TwoFactorController extends Controller
{
    public function store(Request $request): ValidationException|JsonResponse
    {

        $request->validate([
            'two_factor_code' => ['integer', 'required'],
        ]);
        $user = auth()->user();
        // return response()->json(["front" => $request->input('two_factor_code'), 'back' => $user->two_factor_code ]);
        if ($request->input('two_factor_code') !== $user->two_factor_code) {
            return response()->json(['message' => 'Invalid 2FA code'], 401);
        }
        $user->resetTwoFactorCode();
        $user->device_token = $request->deviceToken;
        $user->save();
        return response()->json($user, 201);
    }

    public function phoneVerify(Request $request): JsonResponse
    {

        $request->validate([
            'idToken' => ['string', 'required'],
            'phone' => ['string', 'required']
        ]);
        $user = auth()->user();
        if ($user->phone !== $request->phone) {
            return response()->json(['error' => 'Wrong phone number!'], 400);
        }
        $defaultAuth = Firebase::auth();
        $idToken = $request->idToken;

        try {

        $verifiedIdToken = $defaultAuth->verifyIdToken($idToken);
        } catch (\Kreait\Firebase\Auth\Token\Exception\InvalidToken $e) {
            return response()->json(['error' => 'Invalid token'], 400);
        }
        $user->resetTwoFactorCode();
        $user->device_token = $request->deviceToken;
        $user->save();
        return response()->json($user, 201);
    }
    public function resend(): JsonResponse
    {
        $user = auth()->user();
        $user->generateTwoFactorCode();
        $user->notify(new SendTwoFactorCode());
        return response()->json(['message' => 'Successfully sent the code'], 201);
    }

}
