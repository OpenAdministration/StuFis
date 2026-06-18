<?php

use App\Providers\AppServiceProvider;
use App\Providers\MarkdownServiceProvider;
use SocialiteProviders\Manager\ServiceProvider as SocialiteProvider;

return [
    AppServiceProvider::class,
    MarkdownServiceProvider::class,
    SocialiteProvider::class,
];
