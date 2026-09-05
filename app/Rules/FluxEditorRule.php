<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FluxEditorRule implements ValidationRule
{
    /**
     * Every tag the default flux:editor can emit.
     *
     * flux:editor boots TipTap with StarterKit plus TextAlign, Underline, Link,
     * Highlight, Subscript and Superscript, so the toolbar *and* the markdown
     * input rules can produce all of these -- e.g. typing "---" inserts an <hr>
     * even though no toolbar button does. Anything the editor can write has to
     * be listed here, otherwise saving a legitimate document fails validation.
     *
     * Deliberately absent:
     * - <table>/<tr>/<td>/<th>, which Flux ships but keeps disabled; add them
     *   here if a form ever enables that extension.
     * - <pre>, i.e. code blocks. We do not want them, but StarterKit has no
     *   off switch reachable from outside Flux's prebuilt bundle: codeBlock
     *   sits inside StarterKit, so the flux:editor hook's disableExtension()
     *   cannot see it. Rejecting the tag here is the only lever we have, at
     *   the cost of a save failing if someone does type "```". Inline <code>
     *   stays allowed -- that is a separate mark with its own toolbar button.
     */
    private const string ALLOWED_TAGS = '<p><br><hr><blockquote><code>'
        .'<strong><em><s><u><mark><sub><sup><a>'
        .'<ul><ol><li>'
        .'<h1><h2><h3><h4><h5><h6>';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleanContent = strip_tags((string) $value, self::ALLOWED_TAGS);
        if ($cleanContent !== $value) {
            $fail(__('errors.flux-editor-malicious-html'));
        }
    }
}
