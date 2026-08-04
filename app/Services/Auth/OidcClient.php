<?php

namespace App\Services\Auth;

use Jumbojett\OpenIDConnectClient;

/**
 * Thin extension of the jumbojett OIDC client that exposes the provider's
 * `end_session_endpoint` (used for RP-Initiated Logout). The base class only
 * reads discovery values through a protected helper, so we surface just the one
 * we need rather than reaching in with reflection.
 */
class OidcClient extends OpenIDConnectClient
{
    /**
     * The IdP's `end_session_endpoint` from discovery, or null if it advertises
     * none. Passing a non-null default makes the base helper return that instead
     * of throwing when the key is absent.
     */
    public function endSessionEndpoint(): ?string
    {
        $endpoint = $this->getProviderConfigValue('end_session_endpoint', '');

        return $endpoint !== '' ? $endpoint : null;
    }
}
