<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Spatie\LaravelIgnition\Exceptions\InvalidConfig;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string $provider
 * @property string $provider_sub
 * @property string $provider_token
 * @property string $provider_token_expiration
 * @property string $provider_refresh_token
 * @property string $provider_refresh_token_expiration
 * @property string $picture_url
 * @property string $iban
 * @property string $address
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePictureUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProviderRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProviderRefreshTokenExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProviderSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProviderToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProviderTokenExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @mixin \Eloquent
 */
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

    public function getGroups(){
        switch ($this->provider){
            case 'keycloak':
                if ($this->provider_token_expiration < now()){

                    $driver = Socialite::driver('keycloak');
                    if (\App::isLocal()){
                        $driver = $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
                    }
                    $user = $driver->userFromToken($this->provider_token);
                    return $user['groups'];
                }
                return "Token too old - refreshing token not yet implemented :/";
            case 'laravelpassport':
                return [
                    'login',
                    #'ref-finanzen',
                    #'ref-finanzen-kv',
                    #'ref-finanzen-belege',
                    #'ref-finanzen-hv',
                    'admin'
                ];
            default:
                throw new InvalidConfig('Provider groups not yet implemented');
        }

    }

    public function getCommittees()
    {
        return ['Studierendenrat (StuRa)',];
    }
}
