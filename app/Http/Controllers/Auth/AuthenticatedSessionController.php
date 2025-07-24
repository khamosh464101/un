<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SendTwoFactorCode;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $request->session()->regenerate();
        // this partd added for 2FA
        $request->user()->generateTwoFactorCode();
        $request->user()->notify(new SendTwoFactorCode());
        $token = $request->user()->createToken('token-name');

        if (!$request->remember) {
        // Short-lived token if not "remember me"
        $token->accessToken->update([
            'expires_at' => now()->addMinutes(1400)
        ]);
    }
        return response([
            'user' => $request->user(),
            'access_token' => $token->plainTextToken,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
