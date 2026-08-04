<?php

namespace App\Extensions\Session;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Database session handler that additionally persists the OIDC session id
 * (`sid`) onto the session row.
 *
 * OIDC Back-Channel Logout (see App\Services\Auth\OidcAuthService::backChannelLogout)
 * needs to find and destroy the exact local session that belongs to a given
 * `sid` when the IdP reports that session ended. The `sid` is stashed into the
 * session store at login (see App\Services\Auth\OidcAuthService::userFromCallback);
 * mirroring it into its own indexed `oidc_sid` column - rather than leaving it
 * buried in the serialized `payload` - is what makes that lookup a single
 * indexed `where('oidc_sid', ...)` instead of an un-queryable full-table scan.
 */
class OidcDatabaseSessionHandler extends DatabaseSessionHandler
{
    #[\Override]
    protected function getDefaultPayload($data)
    {
        $payload = parent::getDefaultPayload($data);

        if ($this->container && $this->container->bound('request')) {
            $request = $this->container->make('request');

            if ($request->hasSession()) {
                // Null when the session isn't an OIDC login (local/stumv auth,
                // or after logout flushes it) - the column is nullable and just
                // stays empty for those rows.
                $payload['oidc_sid'] = $request->session()->get('oidc.sid');
            }
        }

        return $payload;
    }
}
