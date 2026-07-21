<?php

namespace Tests\Pest\Rules;

use App\Rules\NoEmbeddedMacros;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Build a throwaway file on disk and wrap it as an UploadedFile.
 * $entries === null writes $raw bytes (a non-archive file); otherwise a ZIP.
 */
function macroRuleUpload(string $name, ?array $entries, string $raw = ''): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'macro-rule');

    if ($entries === null) {
        file_put_contents($path, $raw);
    } else {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($entries as $entry => $data) {
            $zip->addFromString($entry, $data);
        }
        $zip->close();
    }

    return new UploadedFile($path, $name, null, null, true);
}

/** @return list<string> */
function macroErrors(UploadedFile $file): array
{
    $errors = [];
    (new NoEmbeddedMacros)->validate('uploads.0', $file, function (string $message) use (&$errors): void {
        $errors[] = $message;
    });

    return $errors;
}

it('passes a macro-free OOXML document', function (): void {
    expect(macroErrors(macroRuleUpload('clean.docx', [
        '[Content_Types].xml' => '<x/>',
        'word/document.xml' => '<x/>',
    ])))->toBeEmpty();
});

it('rejects a macro-enabled file renamed to a macro-free extension', function (): void {
    // A .docm smuggled in as .docx — the extension allow-list alone would miss it.
    expect(macroErrors(macroRuleUpload('macro.docx', [
        '[Content_Types].xml' => '<x/>',
        'word/vbaProject.bin' => 'MACRO',
    ])))->not->toBeEmpty();
});

it('rejects OOXML macros regardless of the part path', function (): void {
    expect(macroErrors(macroRuleUpload('macro.xlsx', [
        'xl/vbaProject.bin' => 'MACRO',
    ])))->not->toBeEmpty();
});

it('rejects ODF Basic and script-provider macros', function (): void {
    expect(macroErrors(macroRuleUpload('basic.odt', [
        'mimetype' => 'application/vnd.oasis.opendocument.text',
        'Basic/Standard/Module1.xml' => 'Sub Main',
    ])))->not->toBeEmpty()
        ->and(macroErrors(macroRuleUpload('script.ods', [
            'mimetype' => 'application/vnd.oasis.opendocument.spreadsheet',
            'Scripts/python/evil.py' => 'import os',
        ])))->not->toBeEmpty();
});

it('passes a macro-free ODF document', function (): void {
    expect(macroErrors(macroRuleUpload('clean.ods', [
        'mimetype' => 'application/vnd.oasis.opendocument.spreadsheet',
        'content.xml' => '<x/>',
    ])))->toBeEmpty();
});

it('ignores non-archive uploads such as images and PDFs', function (): void {
    expect(macroErrors(macroRuleUpload('photo.png', null, "\x89PNG\r\n\x1a\n")))->toBeEmpty()
        ->and(macroErrors(macroRuleUpload('doc.pdf', null, '%PDF-1.7')))->toBeEmpty();
});
