<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI'),
        'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
        'realms' => env('KEYCLOAK_REALM')         // Specify your keycloak realm
    ],

    'laravelpassport' => [
        'client_id' => env('STUMV_CLIENT_ID'),
        'client_secret' => env('STUMV_CLIENT_SECRET'),
        'redirect' => env('STUMV_REDIRECT_URI'),
        'host' => env('STUMV_HOST'),
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



];
