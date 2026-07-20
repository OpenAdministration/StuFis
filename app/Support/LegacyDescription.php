<?php

namespace App\Support;

use App\Rules\FluxEditorRule;

/**
 * Converts legacy plain-text project descriptions into the HTML that the
 * flux:editor and the raw-HTML show view now expect.
 *
 * Old projects stored `projekte.beschreibung` as plain text with real "\n"
 * line breaks. Since the 4.4 rewrite the field is edited with flux:editor and
 * rendered as raw HTML ({!! $project->beschreibung !!}), where bare newlines
 * collapse to spaces — so the line breaks are lost in both the editor and the
 * view. This turns such plain text into whitelist-safe HTML (only <p>/<br>,
 * all content entity-escaped) so it survives {@see FluxEditorRule}.
 */
class LegacyDescription
{
    /**
     * A value is treated as legacy plain text when it is non-empty and contains
     * no HTML tags. strip_tags() only removes complete tag-like tokens, so a
     * stray "<" (e.g. "a < b") still counts as plain text, while any real tag
     * (e.g. "<p>" or "<group>") marks it as content that must be left untouched.
     */
    public static function isPlainText(string $value): bool
    {
        return trim($value) !== '' && strip_tags($value) === $value;
    }

    /**
     * Convert plain text with "\n" line breaks into whitelist-safe HTML:
     * blank lines separate <p> paragraphs, single newlines become <br>, and all
     * content is entity-escaped so it cannot introduce markup.
     */
    public static function toHtml(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);

        return collect(preg_split('/\n{2,}/', e($normalized))) // e() escapes <, >, &, quotes
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter(fn (string $paragraph): bool => $paragraph !== '')
            ->map(fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', $paragraph).'</p>')
            ->implode('');
    }
}
