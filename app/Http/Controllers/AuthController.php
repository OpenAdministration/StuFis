<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use const http\Client\Curl\AUTH_GSSNEG;

class AuthController
{

    public function login(){
        return Socialite::driver('keycloak')->scopes(['openid', 'profile', 'phone', 'address', 'roles', 'groups'])->stateless()->redirect();
    }

    public function callback(){
        if (Auth::guest()) {
            $userByProvider = Socialite::driver('keycloak')->stateless()->user();
            $tokenResponse = $userByProvider->accessTokenResponseBody;
            $user = User::updateOrCreate([
                'provider_sub' => $userByProvider->user['sub'],
                'provider' => 'keycloak'
            ], [
                'name' => $userByProvider->name,
                'username' => $userByProvider->user['preferred_username'],
                'email' => $userByProvider->user['email'],
                'provider_token' => $userByProvider->token,
                'provider_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
                'provider_refresh_token' => $userByProvider->refreshToken,
                'provider_refresh_token_expiration' => now()->addSeconds($tokenResponse['refresh_expires_in']),
                'picture_url' => $userByProvider->user['picture'] ?? '',
                'iban' => $userByProvider->user['iban'] ?? '',
                'address' => implode(' ', $userByProvider->user['address']),
            ]);
            Auth::login($user);
            //return view('components.dump', ['dump' => $userByProvider]);
        }
        return redirect('/');

    }

    public function logout() {
        Auth::logout();
        // Logout of the laravel app and fwd to keycloak logout
        return redirect(Socialite::driver('keycloak')->getLogoutUrl());
    }
}
