<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuthController
{
    public function __construct(private readonly AuthService $authService) {}

    public function login()
    {
        return $this->authService->prepareLogin();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (Auth::guest() && ! \App::runningUnitTests()) {
            [$identifiers, $userAttributes] = $this->authService->userFromCallback($request);

            $user = User::updateOrCreate($identifiers, $userAttributes);

            Auth::login($user);
        }

        return redirect()->intended(route('home'));
    }

    public function logout()
    {
        Auth::logout();

        // call after logout routine
        return $this->authService->afterLogout();
    }

    /**
     * Out-of-band logout notification from the identity provider (OIDC
     * Back-Channel Logout). Stateless server-to-server POST; the active auth
     * service verifies the token and tears down the matching session(s), or
     * responds 404 if it doesn't support this.
     */
    public function backChannelLogout(): Response
    {
        return $this->authService->backChannelLogout();
    }
}
