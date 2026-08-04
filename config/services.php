<?php

return [

    'stumv' => [
        // using socialiteproviders/laravelpassport
        'client_id' => env('STUMV_CLIENT_ID'),
        'client_secret' => env('STUMV_CLIENT_SECRET'),
        'redirect' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/auth/callback',
        'host' => env('STUMV_HOST'),
        // Prefix for StuMV's OAuth-protected user/committees/groups API.
        // StuMV moved this surface from /api/* to /api-legacy/* (the /api/*
        // prefix is now a separate client-credentials Directory API), so the
        // prefix is configurable to survive future moves without a code change.
        'api_path' => trim((string) env('STUMV_API_PATH', 'api-legacy'), '/'),
        // Userinfo endpoint the LaravelPassport Socialite driver calls (it
        // defaults to 'api/user'); keep it under the same configurable prefix.
        'userinfo_uri' => trim((string) env('STUMV_API_PATH', 'api-legacy'), '/').'/user',
        'logout_path' => env('STUMV_LOGOUT_PATH', 'logout'),
        'mapping' => [
            'login' => env('STUMV_GROUP_LOGIN', 'login'),
            'ref-finanzen' => env('STUMV_GROUP_REVISION'),
            'ref-finanzen-belege' => env('STUMV_GROUP_INVOICE'),
            'ref-finanzen-kv' => env('STUMV_GROUP_KV'),
            'ref-finanzen-hv' => env('STUMV_GROUP_HV'),
            'admin' => env('STUMV_GROUP_ADMIN'),
        ],
    ],

    'oidc' => [
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'provider_url' => env('OIDC_PROVIDER_URL'),
        'certificate_path' => env('OIDC_CERT_PATH'),
        'scopes' => explode(' ', (string) env('OIDC_SCOPES', 'openid profile email')),
        'verify_host' => env('OIDC_VERIFY_HOST', true),
        // Where the browser lands after logout; also sent to the IdP as
        // post_logout_redirect_uri (must be registered on the IdP client).
        // Empty falls back to the login route.
        'post_logout_redirect' => env('OIDC_POST_LOGOUT_REDIRECT'),
        // When true (the default), the IdP is asked to show its logout-
        // confirmation prompt (id_token_hint is withheld) - so the user
        // confirms before their whole SSO session ends. Set false for a
        // seamless logout without a prompt.
        'logout_confirm' => env('OIDC_LOGOUT_CONFIRM', true),
        'attribute-mapping' => [
            'uid' => env('OIDC_ATTRIBUTE_UID', 'sub'),
            'username' => env('OIDC_ATTRIBUTE_USERNAME', 'username'),
            'name' => env('OIDC_ATTRIBUTE_NAME', 'name'),
            'email' => env('OIDC_ATTRIBUTE_EMAIL', 'email'),
            'picture_url' => env('OIDC_ATTRIBUTE_PICTURE_URL', 'avatar'),
            'iban' => env('OIDC_ATTRIBUTE_IBAN', 'iban'),
            'address' => env('OIDC_ATTRIBUTE_ADDRESS', 'address'),
            'groups' => env('OIDC_ATTRIBUTE_GROUP', 'groups'),
            'committees' => env('OIDC_ATTRIBUTE_COMMITTEES', 'committees'),
        ],
        'group-mapping' => [
            'login' => env('OIDC_GROUP_LOGIN', 'login'),
            'ref-finanzen' => env('OIDC_GROUP_REVISION'),
            'ref-finanzen-belege' => env('OIDC_GROUP_INVOICE'),
            'ref-finanzen-kv' => env('OIDC_GROUP_KV'),
            'ref-finanzen-hv' => env('OIDC_GROUP_HV'),
            'admin' => env('OIDC_GROUP_ADMIN'),
        ],
    ],

];
