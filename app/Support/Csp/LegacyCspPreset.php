<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Permissive CSP for the legacy system, which is embedded via <iframe srcdoc> and
 * therefore inherits the embedding page's policy. The legacy app predates CSP: it
 * ships inline <script>/onclick handlers, inline styles and its own same-origin JS
 * bundles, none of which can be nonced. Applying the strict app-wide policy here
 * would break it, so legacy routes get this relaxed policy instead (attached as
 * route-level middleware in routes/legacy.php; spatie lets the innermost middleware
 * win, so the global strict policy backs off for these routes).
 *
 * NOTE: no nonce is added on purpose — a nonce would make the browser ignore
 * 'unsafe-inline', which is exactly what the legacy inline scripts/styles rely on.
 * The strict Basic policy still governs every modern (non-legacy) route.
 */
class LegacyCspPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_INLINE, Keyword::UNSAFE_EVAL])
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::IMG, [Keyword::SELF, 'data:', 'https:'])
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE);
    }
}
