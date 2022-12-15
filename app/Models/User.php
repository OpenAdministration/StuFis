<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Spatie\LaravelIgnition\Exceptions\InvalidConfig;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'provider',
        'provider_sub',
        'provider_token',
        'provider_refresh_token',
        'picture_url',
        'iban',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    function getGroups(){
        switch ($this->provider){
            case 'keycloak':
                if ($this->provider_token_expiration < now()){
                    $user = Socialite::driver('keycloak')->stateless()->userFromToken($this->provider_token);
                    return $user['groups'];
                }
                return "Token too old - refreshing token not yet implemented :/";
            default:
                throw new InvalidConfig('Provider groups not yet implemented');
        }

    }
}
