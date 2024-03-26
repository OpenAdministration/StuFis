<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{

    public function __construct(private readonly AuthService $authService){}

    public function login(){
        return $this->authService->prepareLogin();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (Auth::guest()) {
            [$identifiers, $userAttributes] = $this->authService->userFromCallback($request);

            $user = User::updateOrCreate($identifiers, $userAttributes);

            Auth::login($user);
        }
        return redirect()->intended(RouteServiceProvider::HOME);

    }

    private function remap_keycloak($user) : array
    {
        $attributes = $user->getRaw();
        $tokenResponse = $user->accessTokenResponseBody;
        $uniqueAttributes = [
            'provider_sub' => $attributes['sub'],
            'provider' => $this->driverName,
        ];
        $updateableAttributes = [
            'name' => $user->name,
            'username' => $attributes['preferred_username'],
            'email' => $attributes['email'],
            'provider_token' => $user->token,
            'provider_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
            'provider_refresh_token' => $user->refreshToken,
            'provider_refresh_token_expiration' => now()->addSeconds($tokenResponse['refresh_expires_in']),
            'picture_url' => $attributes['picture'] ?? '',
            'iban' => $attributes['iban'] ?? '',
            'address' => implode(' ', $attributes['address']),
        ];
        return [$uniqueAttributes, $updateableAttributes];
    }

    public function logout() {
        Auth::logout();
        // call after logout routine
        return $this->authService->afterLogout();
    }
}
