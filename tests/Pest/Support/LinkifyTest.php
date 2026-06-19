<?php

use App\View\Components\Linkify;

// Direct contract tests for Linkify::segments(), which splits already-escaped slot
// HTML into plain-text and link segments. It auto-links project references
// (IP-<jahr>-<projekt_id>-A<auslagen_id>) and plain http(s) URLs.

/** @return list<array<string, string>> */
function segmentsFor(string $html): array
{
    return (new Linkify)->segments($html);
}

it('returns a single text segment when there is nothing to link', function (): void {
    expect(segmentsFor('just some purpose text'))
        ->toBe([['type' => 'text', 'html' => 'just some purpose text']]);
});

it('links a project reference and keeps the surrounding text', function (): void {
    $segments = segmentsFor('IP-24-26-A79 - Dezember - Hosting');

    expect($segments)->toHaveCount(2);
    expect($segments[0])->toMatchArray(['type' => 'link', 'html' => 'IP-24-26-A79']);
    expect($segments[0]['href'])->toContain('projekt/26/auslagen/79');
    expect($segments[1])->toBe(['type' => 'text', 'html' => ' - Dezember - Hosting']);
});

it('links text both before and after a project reference', function (): void {
    $segments = segmentsFor('Rechnung IP-24-26-A79 bezahlt');

    expect($segments)->toHaveCount(3);
    expect($segments[0])->toBe(['type' => 'text', 'html' => 'Rechnung ']);
    expect($segments[1]['type'])->toBe('link');
    expect($segments[2])->toBe(['type' => 'text', 'html' => ' bezahlt']);
});

it('links multiple project references', function (): void {
    $links = array_filter(segmentsFor('IP-24-26-A79 und IP-25-3-A1'), fn ($s) => $s['type'] === 'link');

    expect($links)->toHaveCount(2);
});

it('links a plain URL to itself', function (): void {
    $segments = segmentsFor('siehe https://example.com/foo');

    expect($segments)->toHaveCount(2);
    expect($segments[1])->toBe([
        'type' => 'link',
        'html' => 'https://example.com/foo',
        'href' => 'https://example.com/foo',
    ]);
});

it('excludes trailing sentence punctuation from a URL', function (): void {
    $segments = segmentsFor('mehr unter https://example.com.');

    expect($segments[1]['html'])->toBe('https://example.com');
    expect($segments[1]['href'])->toBe('https://example.com');
    expect($segments[2])->toBe(['type' => 'text', 'html' => '.']);
});

it('decodes HTML entities for the href but keeps the display escaped', function (): void {
    // The slot arrives HTML-escaped, so a query string ampersand is "&amp;".
    $segments = segmentsFor('link https://example.com/?a=1&amp;b=2');

    expect($segments[1]['html'])->toBe('https://example.com/?a=1&amp;b=2');
    expect($segments[1]['href'])->toBe('https://example.com/?a=1&b=2');
});

it('links both a project reference and a URL in one string', function (): void {
    $types = array_column(segmentsFor('IP-24-26-A79 belegt via https://example.com'), 'type');

    expect($types)->toBe(['link', 'text', 'link']);
});

it('lets the enclosing URL win over a project token nested inside it', function (): void {
    $segments = segmentsFor('https://example.com/IP-24-26-A79');

    expect($segments)->toHaveCount(1);
    expect($segments[0])->toMatchArray([
        'type' => 'link',
        'html' => 'https://example.com/IP-24-26-A79',
    ]);
});
