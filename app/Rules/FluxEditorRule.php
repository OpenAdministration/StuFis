<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FluxEditorRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleanContent = strip_tags((string) $value, '<p><s><br><strong><em><ul><ol><li><a><h1><h2><h3>');
        if ($cleanContent !== $value) {
            $fail(__('errors.flux-editor-malicious-html'));
        }
    }
}
