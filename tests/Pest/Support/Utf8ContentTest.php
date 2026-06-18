<?php

use Illuminate\Http\Testing\File;

// Direct contract tests for the utf8Content() helper. It has broken twice in history:
//   1. mb_detect_encoding(['Windows-1252','UTF-8']) reported real UTF-8 as Windows-1252
//      and double-converted it -> umlauts became mojibake ("Ãœ").
//   2. finfo + strict iconv() threw on large files / odd bytes -> "wrong file format".
// These tests pin the behaviour so neither regression can come back unnoticed.

/** Wrap raw bytes in a fake upload, exactly as the CSV importer feeds the helper. */
function uploadWith(string $bytes): File
{
    return File::createWithContent('statement.csv', $bytes);
}

it('leaves valid UTF-8 untouched (no double conversion to mojibake)', function (): void {
    // "Euro-Überweisung" already in UTF-8 (Ü = C3 9C). The old mb_detect version
    // mangled this into "Euro-Ãœberweisung".
    $utf8 = "Empf;Euro-\xC3\x9Cberweisung";

    expect(utf8Content(uploadWith($utf8)))->toBe('Empf;Euro-Überweisung');
});

it('converts a Windows-1252 umlaut to UTF-8', function (): void {
    // ü as the single Windows-1252 byte 0xFC.
    expect(utf8Content(uploadWith("M\xFCller")))->toBe('Müller');
});

it('decodes the Euro sign from Windows-1252 (0x80), not ISO-8859-1', function (): void {
    // 0x80 is "€" in Windows-1252 but undefined in ISO-8859-1 — the encoding the old
    // finfo path detected. This is what makes German bank statements with € correct.
    expect(utf8Content(uploadWith("Betrag 100 \x80")))->toBe('Betrag 100 €');
});

it('leaves plain ASCII unchanged', function (): void {
    expect(utf8Content(uploadWith("date;empf;value\n01.01.2026;ACME;-10,00")))
        ->toBe("date;empf;value\n01.01.2026;ACME;-10,00");
});

it('never throws on bytes that are not valid UTF-8', function (): void {
    // A stray 0xFF is invalid UTF-8; the strict iconv() version threw here. The helper
    // must always return a valid UTF-8 string instead of crashing the import.
    $out = utf8Content(uploadWith("x\xFFy"));

    expect(mb_check_encoding($out, 'UTF-8'))->toBeTrue();
});
