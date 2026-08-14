<?php

use Composer\InstalledVersions;

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

        /*
         * Source for `stufis:fints-institutes-update`. Die Deutsche Kreditwirtschaft hands
         * its own FinTS-Bankenliste to registered vendors only and forbids shipping it as
         * part of a software product, so we pull hbci4java's public equivalent instead.
         */
        'institute_list_url' => env(
            'FINTS_INSTITUTE_LIST_URL',
            'https://raw.githubusercontent.com/hbci4j/hbci4java/master/src/main/resources/blz.properties',
        ),
    ],

    'version' => InstalledVersions::getPrettyVersion('openadministration/stufis'),

    'admin_mail' => env('HELP_CONTACT_MAIL', 'stufis@open-administration.de'), // unused?

    'profile_name' => env('PROFILE_NAME', 'StuMV Profil'),
    'profile_url' => env('PROFILE_URL', 'https://stumv.open-administration.de/profile'),

    'about_url' => env('ABOUT_URL', 'https://open-administration.de/index.php/kontakt-und-impressum/'),
    'privacy_url' => env('PRIVACY_URL', 'https://open-administration.de/index.php/datenschutz/'),
    'terms_url' => env('TERMS_URL', 'https://open-administration.de/index.php/nutzungsbedingungen/'),
    'git_url' => env('GIT_URL', 'https://github.com/openadministration/stufis/releases'),
    'blog_url' => env('BLOG_URL', 'https://open-administration.de'),
    'docs_url' => env('DOCS_URL', 'https://doku.stufis.de'),

];
