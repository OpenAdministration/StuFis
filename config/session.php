<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | Defaults to "database" here rather than in the environment, so real
    | deployments need no SESSION_DRIVER entry (it is dropped from .env.example).
    | OIDC Back-Channel Logout depends on it: the IdP's logout notification
    | arrives without a browser session and finds the session to end via the
    | indexed `oidc_sid` column on the sessions table (see the
    | create_sessions_table migration and
    | App\Extensions\Session\OidcDatabaseSessionHandler). A file or cookie driver
    | would silently break single-logout.
    |
    | It stays overridable via SESSION_DRIVER on purpose: the test suite sets it
    | to "array" (see .env.testing) to avoid touching the database. Every other
    | session option is inherited from the framework default config (Laravel
    | merges this file over it), so SESSION_LIFETIME, SESSION_ENCRYPT,
    | SESSION_PATH, SESSION_DOMAIN, etc. continue to work as before.
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | Defaults to true (the framework default is false): sessions live in the
    | database and their payload carries authorization data (the user's mapped
    | groups and committees), so the server-side store is encrypted at rest with
    | APP_KEY as defense in depth. This does not touch the indexed `oidc_sid`
    | column Back-Channel Logout queries - only the serialized payload is
    | encrypted.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', true),

];
