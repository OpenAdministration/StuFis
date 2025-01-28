<?php

return [
    /**
     * stable - only stable stuff
     * preview - beta branch - usually not linked in UI but can be found by URI
     * dev - for local development only - not bug free
     */
    'features' => env('STUFIS_FEATURE_BRANCH', 'stable'),
];
