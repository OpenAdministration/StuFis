<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    private string $driverName;

    public function __construct()
    {
        $this->driverName = config('auth.socialite');
    }

    public function login(){
        $driver = Socialite::driver($this->driverName);
        match ($this->driverName){
            'keycloak' => $driver->scopes(['openid', 'profile', 'phone', 'address', 'roles', 'groups'])->stateless(),
            'laravelpassport' => $driver,
        };
        return $driver->redirect();
    }

    public function callback(){
        if (Auth::guest()) {
            $driver = Socialite::driver($this->driverName);
            // if we have a local dev instance of stumv there is no need to verify ssl certs
            if (\App::isLocal()){
                $driver = $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }
            $userByProvider = match ($this->driverName){
                'keycloak' => $driver->stateless()->user(),
                'laravelpassport' => $driver->user(),
            };
            [$uniqueIds, $attributes] = match ($this->driverName){
                'keycloak' => $this->remap_keycloak($userByProvider),
                'laravelpassport' => $this->remap_laravelpassport($userByProvider),
            };

            $user = User::updateOrCreate($uniqueIds, $attributes);

            Auth::login($user);
            //return view('components.dump', ['dump' => $userByProvider]);
        }
        return redirect('/');

    }

    private function remap_laravelpassport($user)
    {
        $attributes = $user->getRaw();
        $tokenResponse = $user->accessTokenResponseBody;
        $uniqueAttributes = [
            'provider_sub' => $attributes['id'],
            'provider' => $this->driverName,
        ];
        $updateableAttributes = [
            'name' => $attributes['name'],
            'username' => $attributes['nickname'],
            'email' => $attributes['email'],
            'provider_token' => $user->token,
            'provider_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
            'provider_refresh_token' => $user->refreshToken,
            'provider_refresh_token_expiration' => now()->addSeconds($tokenResponse['expires_in']),
            'picture_url' => $attributes['avatar'] ?? '',
            'iban' => $attributes['iban'] ?? '',
            'address' => $attributes['address'] ?? '',
        ];
        return [$uniqueAttributes, $updateableAttributes];
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
        $redirect_url = match ($this->driverName){
            'keycloak' => Socialite::driver($this->driverName)->getLogoutUrl(),
            'laravelpassport' => config('services.laravelpassport.host') . config('services.laravelpassport.logout_path')
        };
        Auth::logout();
        // Logout of the laravel app and fwd to keycloak logout
        return redirect(to: $redirect_url);
    }
}
