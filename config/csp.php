<?php

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Nonce\RandomString;
use Spatie\Csp\Presets\Basic;

// stumv identity-provider origin, so user avatars it serves pass img-src without
// opening up all of https:. STUMV_HOST already holds the full origin; just drop any
// trailing slash. Read from env() (not config()) — config-file load order isn't guaranteed.
$stumvOrigin = ($host = env('STUMV_HOST')) ? rtrim((string) $host, '/') : null;

return [

    /*
     * Presets will determine which CSP headers will be set. A valid CSP preset is
     * any class that implements `Spatie\Csp\Preset`
     */
    'presets' => [
        // ENFORCING policy (4.5.0): the strict `'self'`-everywhere baseline with
        // nonces on script/style. Violations are now blocked by the browser via
        // the `Content-Security-Policy` header, not merely reported.
        Basic::class,
    ],

    /**
     * Register additional global CSP directives here.
     */
    'directives' => [
        // 'script-src' stays strict ('self' + nonce, no unsafe-*). Only inline
        // style ATTRIBUTES are relaxed: a handful of data-driven widths/indents
        // (budget consumption-meter, view-row indent, project progress bar) render
        // as `style="width:..%"`, which the enforcing style-src would otherwise
        // block. <style> ELEMENTS still require the nonce via style-src.
        [Directive::STYLE_ATTR, [Keyword::UNSAFE_INLINE]],

        // Allow user avatars served by the stumv identity provider (external
        // `picture_url`) plus data: URIs — scoped to the provider origin rather
        // than all of https:. img-src is a resource-load control, not a script/XSS
        // vector, so this doesn't weaken the strict script-src.
        [Directive::IMG, array_values(array_filter([Keyword::SELF, 'data:', $stumvOrigin]))],
    ],

    /*
     * These presets which will be put in a report-only policy. This is great for testing out
     * a new policy or changes to existing CSP policy without breaking anything.
     */
    'report_only_presets' => [
        //
    ],

    /**
     * Register additional global report-only CSP directives here.
     */
    'report_only_directives' => [
        // [Directive::SCRIPT, [Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE]],
    ],

    /*
     * All violations against a policy will be reported to this url.
     * A great service you could use for this is https://report-uri.com/
     */
    'report_uri' => env('CSP_REPORT_URI', ''),

    /*
     * Optional separate report url for the report-only policy. When empty,
     * the report-only policy falls back to `report_uri` above. Useful for
     * services like report-uri.com that require different paths for enforcing
     * (`/enforce`) and report-only (`/reportOnly`) policies.
     */
    'report_only_uri' => env('CSP_REPORT_ONLY_URI', ''),

    /*
     * The name of the reporting endpoint that violations should be sent to.
     * The endpoint itself must be defined in `reporting_endpoints` below.
     */
    'report_to' => env('CSP_REPORT_TO', ''),

    /*
     * Optional separate reporting endpoint name for the report-only policy.
     * When empty, the report-only policy falls back to `report_to` above.
     */
    'report_only_to' => env('CSP_REPORT_ONLY_TO', ''),

    /*
     * Reporting endpoints that will be sent in the `Reporting-Endpoints` HTTP
     * header. The keys are the endpoint names that can be referenced from
     * `report_to` above.
     *
     * Example: ['default' => 'https://example.com/csp-reports']
     */
    'reporting_endpoints' => [
        //
    ],

    /*
     * Headers will only be added if this setting is set to true.
     */
    'enabled' => env('CSP_ENABLED', true),

    /**
     * Headers will be added when Vite is hot reloading.
     */
    'enabled_while_hot_reloading' => env('CSP_ENABLED_WHILE_HOT_RELOADING', false),

    /*
     * The class responsible for generating the nonces used in inline tags and headers.
     */
    'nonce_generator' => RandomString::class,

    /*
     * Set false to disable automatic nonce generation and handling.
     * This is useful when you want to use 'unsafe-inline' for scripts/styles
     * and cannot add inline nonces.
     * Note that this will make your CSP policy less secure.
     */
    'nonce_enabled' => env('CSP_NONCE_ENABLED', true),
];
