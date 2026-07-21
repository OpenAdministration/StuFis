<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;
use ZipArchive;

/**
 * Rejects office documents (OOXML: docx/xlsx/pptx; ODF: odt/ods/odp) that carry
 * embedded macros — including a macro-enabled file (docm/xlsm) renamed to a
 * macro-free extension to slip past an extension allow-list.
 *
 * Office documents are ZIP containers, so we open the archive and look for the
 * concrete macro artefacts:
 *   - OOXML stores VBA in a `vbaProject.bin` part (never present in a genuine
 *     macro-free docx/xlsx).
 *   - ODF stores macros under the `Basic/` (Basic) or `Scripts/` (Python/JS/…)
 *     directories.
 *
 * Non-archive uploads (pdf, jpg, png, …) are not ZIPs and pass untouched, so the
 * rule is safe to apply to any file field.
 */
class NoEmbeddedMacros implements ValidationRule
{
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

        $zip = new ZipArchive;

        // Not a ZIP archive → cannot be an OOXML/ODF document → nothing to check.
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);

                if ($entry !== false && $this->isMacroEntry($entry)) {
                    $fail(__('errors.attachment-contains-macros'));

                    return;
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Whether a ZIP entry name is a macro artefact of an OOXML or ODF document.
     */
    private function isMacroEntry(string $entry): bool
    {
        $entry = strtolower(str_replace('\\', '/', $entry));

        return str_ends_with($entry, 'vbaproject.bin') // OOXML VBA (docm/xlsm/pptm)
            || str_starts_with($entry, 'basic/')        // ODF Basic macros
            || str_starts_with($entry, 'scripts/');      // ODF script-provider macros
    }
}
