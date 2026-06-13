<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withCache(
        // specify a path that works locally as well as on CI job runners
        cacheDirectory: '/tmp/rector',

        // ensure file system caching is used instead of in-memory
        cacheClass: FileCacheStorage::class
    )
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
    ])
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/bootstrap/providers.php',
        __DIR__.'/config',
        // __DIR__.'/lang', // translation arrays only — nothing for Rector to do
        // __DIR__.'/legacy',
        // __DIR__.'/public', // framework-generated bootstrap; leave untouched
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPhpSets() // defaults to the php version from composer
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(78)
    ->withSkip([
        // compact() is idiomatic in Laravel controllers; the explicit-array rewrite is
        // style-only and has an undefined-variable edge case. Keep compact().
        CompactToVariablesRector::class,
        // declare(strict_types=1) is a behavioral change (scalar coercion -> TypeError);
        // adopt deliberately with a green test suite, not via a blanket Rector run.
        SafeDeclareStrictTypesRector::class,
    ]);
