<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;
use ZipArchive;

/**
 * Verifies an uploaded file's actual bytes match its declared extension.
 *
 * Laravel's onboard content checks can't do this reliably: finfo reports every
 * zip-based office format (docx/xlsx/pptx/odf) as "application/zip", so the
 * `mimes`/`mimetypes` rules either false-reject valid documents or wave through
 * anything that happens to be a zip. This rule instead inspects the real
 * structure — magic bytes for pdf/jpg/png, and the container layout for OOXML
 * (an OPC `[Content_Types].xml` part) and ODF (a `mimetype` media type or the
 * package manifest).
 *
 * This is defense-in-depth; the real boundary is the serving layer
 * (extension-derived Content-Type + nosniff, see ProjectController). Its job is
 * to reject a file whose bytes clearly aren't what its name claims — e.g. an
 * .html/.exe/.zip renamed to .docx or .png. Unknown extensions pass untouched
 * (the extension allow-list gates those).
 */
class ContentMatchesExtension implements ValidationRule
{
    private const array ODF_MIME_TYPES = [
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
    ];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $path = $value->getRealPath();

        if ($path === false || $path === '') {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        $matches = match ($extension) {
            'pdf' => $this->startsWith($path, '%PDF-'),
            'jpg', 'jpeg' => $this->startsWith($path, "\xFF\xD8\xFF"),
            'png' => $this->startsWith($path, "\x89PNG\r\n\x1a\n"),
            'docx', 'xlsx', 'pptx' => $this->isOoxmlPackage($path),
            'odt', 'ods', 'odp' => $this->isOdfPackage($path, $extension),
            default => true, // not our concern — the allow-list rule gates unknown extensions
        };

        if (! $matches) {
            $fail(__('errors.attachment-content-mismatch'));
        }
    }

    /**
     * Whether the file begins with the given magic-byte signature.
     */
    private function startsWith(string $path, string $signature): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            return fread($handle, strlen($signature)) === $signature;
        } finally {
            fclose($handle);
        }
    }

    /**
     * A genuine OOXML file is an OPC package: a zip containing [Content_Types].xml.
     */
    private function isOoxmlPackage(string $path): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            return $zip->locateName('[Content_Types].xml', ZipArchive::FL_NOCASE) !== false;
        } finally {
            $zip->close();
        }
    }

    /**
     * A genuine ODF file is a zip carrying its media type in a `mimetype` entry;
     * the package manifest is accepted as a fallback for producers that omit it.
     */
    private function isOdfPackage(string $path, string $extension): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            $mimetype = $zip->getFromName('mimetype');

            if ($mimetype !== false && $mimetype === (self::ODF_MIME_TYPES[$extension] ?? null)) {
                return true;
            }

            return $zip->locateName('META-INF/manifest.xml', ZipArchive::FL_NOCASE) !== false;
        } finally {
            $zip->close();
        }
    }
}
