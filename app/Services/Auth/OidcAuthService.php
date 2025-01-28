<?php

namespace App\Services\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Jumbojett\OpenIDConnectClient;

class OidcAuthService extends AuthService
{
    private $oidc;

    public function __construct()
    {
        $this->oidc = new OpenIDConnectClient(
            config('services.oidc.provider_url'),
            config('services.oidc.client_id'),
            config('services.oidc.client_secret'),
        );
        if (! empty(config('services.oidc.certificate_path'))) {
            $this->oidc->setCertPath(config('services.oidc.certificate_path'));
        }
        $this->oidc->setVerifyHost(config('services.oidc.verify_host'));
        $this->oidc->setRedirectURL(url('/auth/callback'));
        if (! empty(config('services.oidc.scopes'))) {
            $this->oidc->addScope(config('services.oidc.scopes'));
        }
    }

    public function prepareLogin(): Response|RedirectResponse
    {
        // redirect to IdP if unauthenticated
        $this->oidc->authenticate();

        // will never be reached usually
        return redirect()->to('/auth/callback');
    }

    public function userFromCallback(Request $request): array
    {
        // check response
        $this->oidc->authenticate();

        session(['oidc.token' => $this->oidc->getAccessToken()]);

        $attributes = $this->oidc->requestUserInfo();

        $attributeMapping = config('services.oidc.attribute-mapping');

        $identifiers = [
            'provider_uid' => $attributes->{$attributeMapping['uid']},
            'provider' => 'oidc',
        ];
        $userAttributes = [
            'name' => $attributes->{$attributeMapping['name']},
            'username' => $attributes->{$attributeMapping['username']},
            'email' => $attributes->{$attributeMapping['email']},
            'picture_url' => $attributes->{$attributeMapping['picture_url']} ?? '',
            'iban' => $attributes->{$attributeMapping['iban']} ?? '',
            'address' => $attributes->{$attributeMapping['address']} ?? '',
        ];

        session([
            'oidc.groups-raw' => $attributes->{$attributeMapping['groups']},
            'oidc.committees' => $attributes->{$attributeMapping['committees']},
            'oidc.all-committees' => $attributes->{$attributeMapping['all-committees']},
        ]);

        return [$identifiers, $userAttributes];
    }

    public function userCommittees(): Collection
    {
        return collect(session('oidc.committees'));
    }

    public function userGroupsRaw(): Collection
    {
        return collect(session('oidc.groups-raw'));
    }

    public function groupMapping(): Collection
    {
        return collect(config('services.oidc.group-mapping'));
    }

    public function afterLogout(): RedirectResponse
    {
        \Session::flush();

        return redirect()->to(config('services.oidc.logout_url'));
    }

    public function allCommittees(): Collection
    {
        return collect(session('oidc.all-committees'));
    }
}
