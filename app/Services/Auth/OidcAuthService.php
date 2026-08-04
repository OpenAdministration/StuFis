<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Jumbojett\OpenIDConnectClientException;

class OidcAuthService extends AuthService
{
    private $oidc;

    // The client is injectable so tests can supply a double; the container
    // resolves this service with no arguments and gets the configured client.
    public function __construct(?OidcClient $client = null)
    {
        $this->oidc = $client ?? $this->makeClient();
    }

    private function makeClient(): OidcClient
    {
        $client = new OidcClient(
            config('services.oidc.provider_url'),
            config('services.oidc.client_id'),
            config('services.oidc.client_secret'),
        );
        if (! empty(config('services.oidc.certificate_path'))) {
            $client->setCertPath(config('services.oidc.certificate_path'));
        }
        $client->setVerifyHost(config('services.oidc.verify_host'));
        $client->setRedirectURL(url('/auth/callback'));
        if (! empty(config('services.oidc.scopes'))) {
            $client->addScope(config('services.oidc.scopes'));
        }

        return $client;
    }

    #[\Override]
    public function prepareLogin(): Response|RedirectResponse
    {
        // redirect to IdP if unauthenticated
        $this->oidc->authenticate();

        // will never be reached usually
        return redirect()->to('/auth/callback');
    }

    #[\Override]
    public function userFromCallback(Request $request): array
    {
        // check response
        $this->oidc->authenticate();

        // Persist the id_token's OIDC session id so Back-Channel Logout can later
        // destroy exactly this session (see OidcDatabaseSessionHandler, which
        // mirrors it into the sessions table, and self::backChannelLogout()).
        session(['oidc.sid' => $this->oidc->getVerifiedClaims('sid')]);

        // Persist the id_token itself for use as `id_token_hint` in RP-Initiated
        // Logout (see self::afterLogout).
        session(['oidc.id_token' => $this->oidc->getIdToken()]);

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
            'picture_url' => $this->normalizeUrl($attributes->{$attributeMapping['picture_url']} ?? ''),
            'iban' => $attributes->{$attributeMapping['iban']} ?? '',
            'address' => $attributes->{$attributeMapping['address']} ?? '',
        ];

        session([
            'oidc.groups-raw' => $attributes->{$attributeMapping['groups']} ?? [],
            'oidc.committees' => $attributes->{$attributeMapping['committees']} ?? [],
        ]);

        return [$identifiers, $userAttributes];
    }

    /**
     * Handle an OIDC Back-Channel Logout notification: verify the `logout_token`
     * the IdP POSTed and destroy the local session(s) it targets.
     *
     * Returns a plain response (never throws / redirects) per the Back-Channel
     * Logout 1.0 spec: 200 on success, 400 on an invalid token.
     */
    #[\Override]
    public function backChannelLogout(): Response
    {
        try {
            ['sid' => $sid, 'sub' => $sub] = $this->verifyBackChannelLogoutToken();
        } catch (\Throwable $e) {
            \Log::warning('OIDC back-channel logout rejected: '.$e->getMessage());

            return response('Invalid logout token', Response::HTTP_BAD_REQUEST)
                ->header('Cache-Control', 'no-store');
        }

        $this->destroySessions($sid, $sub);

        return response('', Response::HTTP_OK)->header('Cache-Control', 'no-store');
    }

    /**
     * Verify the `logout_token` against the IdP and return the session
     * identifiers it targets.
     *
     * jumbojett verifies the token's signature against the IdP's JWKS and its
     * claims per the Back-Channel Logout spec (no nonce, an events claim, a
     * matching iss/aud, and a sub and/or sid). Either identifier may be null.
     *
     * @return array{sid: ?string, sub: ?string}
     */
    protected function verifyBackChannelLogoutToken(): array
    {
        // jumbojett reads the token straight from PHP's $_REQUEST superglobal,
        // which isn't populated in every context (tests, non-urlencoded bodies).
        // Mirror it from the framework request; a null value leaves it unset
        // (isset() is false) so jumbojett reports a missing token.
        $token = request()->input('logout_token');
        if ($token !== null) {
            $_REQUEST['logout_token'] = $token;
        } else {
            unset($_REQUEST['logout_token']);
        }

        if (! $this->oidc->verifyLogoutToken()) {
            throw new OpenIDConnectClientException('Back-channel logout token failed claim verification');
        }

        return [
            'sid' => $this->oidc->getVerifiedClaims('sid'),
            'sub' => $this->oidc->getVerifiedClaims('sub'),
        ];
    }

    /**
     * Destroy the local session(s) the logout token targets.
     *
     * A `sid` identifies one specific session (the IdP sends one token per live
     * session), so it's the precise, preferred key. Only when no `sid` is given
     * do we fall back to `sub` and log out every session for that user, as the
     * spec allows.
     */
    protected function destroySessions(?string $sid, ?string $sub): void
    {
        $table = config('session.table', 'sessions');

        if (! in_array($sid, [null, '', '0'], true)) {
            DB::table($table)->where('oidc_sid', $sid)->delete();

            return;
        }

        if (! in_array($sub, [null, '', '0'], true)) {
            $userId = User::query()
                ->where('provider', 'oidc')
                ->where('provider_uid', $sub)
                ->value('id');

            if ($userId !== null) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }
    }

    #[\Override]
    public function userCommittees(): Collection
    {
        return collect(session('oidc.committees'));
    }

    #[\Override]
    public function userGroupsRaw(): Collection
    {
        return collect(session('oidc.groups-raw'));
    }

    #[\Override]
    public function groupMapping(): Collection
    {
        return collect(config('services.oidc.group-mapping'));
    }

    #[\Override]
    public function afterLogout(): RedirectResponse
    {
        // Read the id_token before flushing so it can be used as id_token_hint.
        $idToken = session('oidc.id_token');

        \Session::flush();

        $target = config('services.oidc.post_logout_redirect') ?: route('login');

        // Prefer RP-Initiated Logout at the IdP (which also ends the SSO
        // session); fall back to a plain local redirect if the IdP advertises no
        // logout endpoint or can't be reached.
        return redirect()->away($this->rpInitiatedLogoutUrl($idToken, $target) ?? $target);
    }

    /**
     * Build the IdP's RP-Initiated Logout URL (`end_session_endpoint` with a
     * `post_logout_redirect_uri`), or null if the IdP advertises no logout
     * endpoint or discovery fails - in which case the caller redirects locally.
     */
    private function rpInitiatedLogoutUrl(?string $idToken, string $postLogoutRedirect): ?string
    {
        try {
            $endpoint = $this->oidc->endSessionEndpoint();
        } catch (\Throwable $e) {
            \Log::warning('OIDC end_session discovery failed: '.$e->getMessage());

            return null;
        }

        if (empty($endpoint)) {
            return null;
        }

        $params = ['post_logout_redirect_uri' => $postLogoutRedirect];

        // id_token_hint lets the IdP identify the session and skip its "really
        // log out?" prompt. When OIDC_LOGOUT_CONFIRM is set we omit it so the IdP
        // asks the user; client_id is then sent instead so the IdP can still
        // validate post_logout_redirect_uri against the registered client.
        if (! config('services.oidc.logout_confirm') && ! in_array($idToken, [null, '', '0'], true)) {
            $params['id_token_hint'] = $idToken;
        } else {
            $params['client_id'] = config('services.oidc.client_id');
        }

        return $endpoint.(str_contains((string) $endpoint, '?') ? '&' : '?').http_build_query($params);
    }
}
