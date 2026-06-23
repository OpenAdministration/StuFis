<?php

namespace App\Rules;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Enforces the configurable project-description length bounds
 * (project.description.min_length / max_length).
 *
 * The value is rich-text HTML from the Flux editor, so the tags are stripped
 * and only the visible text is counted. A configured max of -1 disables the
 * upper bound (a min of 0 disables the lower bound).
 */
class DescriptionLengthRule implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $min = (int) Setting::get('project.description.min_length', 0);
        $max = (int) Setting::get('project.description.max_length', -1);

        $length = $this->visibleLength((string) $value);

        if ($min > 0 && $length < $min) {
            $fail(__('errors.description-too-short', ['min' => $min]));
        }

        if ($max >= 0 && $length > $max) {
            $fail(__('errors.description-too-long', ['max' => $max]));
        }
    }

    /**
     * Count of visible characters: HTML stripped, entities decoded, whitespace
     * collapsed and trimmed so markup never inflates the length.
     */
    private function visibleLength(string $value): int
    {
        $text = strip_tags($value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);

        return mb_strlen(trim((string) $text));
    }
}
