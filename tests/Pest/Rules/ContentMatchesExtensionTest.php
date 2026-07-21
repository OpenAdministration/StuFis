<?php

namespace Tests\Pest\Rules;

use App\Rules\ContentMatchesExtension;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Write raw bytes to a throwaway file and wrap it as an UploadedFile named $name.
 */
function contentUpload(string $name, string $bytes): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'content-rule');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * Build a ZIP with the given entries and wrap it as an UploadedFile named $name.
 */
function contentZipUpload(string $name, array $entries): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'content-rule');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    foreach ($entries as $entry => $data) {
        $zip->addFromString($entry, $data);
    }
    $zip->close();

    return new UploadedFile($path, $name, null, null, true);
}

/** @return list<string> */
function contentErrors(UploadedFile $file): array
{
    $errors = [];
    (new ContentMatchesExtension)->validate('uploads.0', $file, function (string $message) use (&$errors): void {
        $errors[] = $message;
    });

    return $errors;
}

it('accepts files whose magic bytes match the extension', function (): void {
    expect(contentErrors(contentUpload('a.pdf', '%PDF-1.7 ...')))->toBeEmpty()
        ->and(contentErrors(contentUpload('a.jpg', "\xFF\xD8\xFF\xE0 ...")))->toBeEmpty()
        ->and(contentErrors(contentUpload('a.jpeg', "\xFF\xD8\xFF\xE0 ...")))->toBeEmpty()
        ->and(contentErrors(contentUpload('a.png', "\x89PNG\r\n\x1a\n ...")))->toBeEmpty();
});

it('rejects a payload disguised behind an image/pdf extension', function (): void {
    $html = '<html><script>alert(1)</script></html>';

    expect(contentErrors(contentUpload('evil.png', $html)))->not->toBeEmpty()
        ->and(contentErrors(contentUpload('evil.pdf', $html)))->not->toBeEmpty()
        ->and(contentErrors(contentUpload('evil.jpg', $html)))->not->toBeEmpty();
});

it('accepts a genuine OOXML package', function (): void {
    expect(contentErrors(contentZipUpload('a.docx', [
        '[Content_Types].xml' => '<x/>',
        'word/document.xml' => '<x/>',
    ])))->toBeEmpty();
});

it('rejects an arbitrary zip renamed to an office extension', function (): void {
    expect(contentErrors(contentZipUpload('fake.docx', [
        'random/file.txt' => 'not an office document',
    ])))->not->toBeEmpty();
});

it('accepts a genuine ODF package by mimetype or by manifest', function (): void {
    // mimetype entry equal to the media type
    expect(contentErrors(contentZipUpload('a.ods', [
        'mimetype' => 'application/vnd.oasis.opendocument.spreadsheet',
        'content.xml' => '<x/>',
    ])))->toBeEmpty()
        // manifest fallback (no mimetype entry)
        ->and(contentErrors(contentZipUpload('a.odt', [
            'META-INF/manifest.xml' => '<manifest/>',
            'content.xml' => '<x/>',
        ])))->toBeEmpty();
});

it('rejects an ODF file whose mimetype does not match the extension', function (): void {
    // A presentation media type in a file claiming to be a spreadsheet, with no manifest.
    expect(contentErrors(contentZipUpload('mismatch.ods', [
        'mimetype' => 'application/vnd.oasis.opendocument.presentation',
    ])))->not->toBeEmpty();
});
