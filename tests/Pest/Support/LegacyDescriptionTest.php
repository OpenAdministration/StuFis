<?php

use App\Rules\FluxEditorRule;
use App\Support\LegacyDescription;

// Contract tests for LegacyDescription, which converts old plain-text project
// descriptions (with "\n" line breaks) into the whitelist-safe HTML the
// flux:editor and raw-HTML show view now expect.

// --- toHtml() ---

it('wraps a single line in a paragraph', function (): void {
    expect(LegacyDescription::toHtml('Hello world'))->toBe('<p>Hello world</p>');
});

it('converts single newlines to <br> within a paragraph', function (): void {
    expect(LegacyDescription::toHtml("line one\nline two"))->toBe('<p>line one<br>line two</p>');
});

it('splits blank-line separated blocks into paragraphs', function (): void {
    expect(LegacyDescription::toHtml("first\n\nsecond"))->toBe('<p>first</p><p>second</p>');
});

it('collapses runs of blank lines into a single paragraph break', function (): void {
    expect(LegacyDescription::toHtml("a\n\n\n\nb"))->toBe('<p>a</p><p>b</p>');
});

it('normalizes windows and mac line endings', function (): void {
    expect(LegacyDescription::toHtml("a\r\nb\rc"))->toBe('<p>a<br>b<br>c</p>');
});

it('escapes html special characters', function (): void {
    expect(LegacyDescription::toHtml('budget < 100 & "cheap"'))
        ->toBe('<p>budget &lt; 100 &amp; &quot;cheap&quot;</p>');
});

it('trims surrounding blank lines', function (): void {
    expect(LegacyDescription::toHtml("\n\nhello\n\n"))->toBe('<p>hello</p>');
});

it('returns an empty string for an empty value', function (): void {
    expect(LegacyDescription::toHtml(''))->toBe('');
});

// --- isPlainText() ---

it('treats text without tags as plain text', function (): void {
    expect(LegacyDescription::isPlainText('just some text'))->toBeTrue();
});

it('treats a stray "<" as plain text', function (): void {
    expect(LegacyDescription::isPlainText('budget a < b'))->toBeTrue();
});

it('treats content with real html tags as not plain text', function (): void {
    expect(LegacyDescription::isPlainText('<p>already html</p>'))->toBeFalse();
});

it('treats a tag-like token as not plain text so it is skipped and logged', function (): void {
    // Documented edge: strip_tags removes "<group>", so this is left untouched.
    expect(LegacyDescription::isPlainText('grant to <group>'))->toBeFalse();
});

it('treats empty or whitespace-only values as not plain text', function (): void {
    expect(LegacyDescription::isPlainText(''))->toBeFalse();
    expect(LegacyDescription::isPlainText("  \n  "))->toBeFalse();
});

// --- integration with the write-time validation rule ---

it('produces html that passes FluxEditorRule', function (): void {
    $html = LegacyDescription::toHtml("Angebot < 100€\n\nsiehe \"Anhang\" & Notizen\nmit Umbruch");

    $failed = false;
    (new FluxEditorRule)->validate('beschreibung', $html, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
