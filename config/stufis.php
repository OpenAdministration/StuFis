<?php

return [
    /**
     * stable - only stable stuff
     * preview - beta branch - usually not linked in UI but can be found by URI
     * dev - for local development only - not bug free
     */
    'features' => env('STUFIS_FEATURE_BRANCH', 'stable'),

    'realm' => env('AUTH_REALM'),

    'fints' => [
        'registration_number' => env('FINTS_REG_NR'),
    ],

    'version' => \Composer\InstalledVersions::getPrettyVersion('openadministration/stufis'),

    'admin_mail' => env('HELP_CONTACT_MAIL', 'stufis@open-administration.de'), // unused?

    'about_url' => env('ABOUT_URL', 'https://open-administration.de/index.php/kontakt-und-impressum/'),
    'privacy_url' => env('PRIVACY_URL', 'https://open-administration.de/index.php/datenschutz/'),
    'terms_url' => env('TERMS_URL', 'https://open-administration.de/index.php/nutzungsbedingungen/'),
    'git_url' => env('GIT_URL', 'https://github.com/openadministration/stufis/releases'),
    'blog_url' => env('BLOG_URL', 'https://open-administration.de'),
    'docs_url' => env('DOCS_URL', 'https://doku.stufis.de'),

];
