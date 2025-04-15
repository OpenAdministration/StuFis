<?php

use Illuminate\Support\Facades\Facade;

return [

    'aliases' => Facade::defaultAliases()->merge([
        'Debugbar' => Barryvdh\Debugbar\Facades\Debugbar::class,
    ])->toArray(),

];
