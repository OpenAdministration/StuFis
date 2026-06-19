<?php

declare(strict_types=1);

namespace App\Support\Import;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Regex\Regex;

/**
 * Pure CSV parsing extracted from the manual-import component: encoding-independent
 * (caller converts to UTF-8 first), separator guessing and per-cell money/date
 * normalization. Returns positional rows the component then maps interactively.
 */
class CsvImportParser
{
    /**
     * @return array{header: array<int, string>, data: Collection<int, array<int, string>>, separator: string}
     */
    public function parse(string $content): array
    {
        $content = str($content);
        $lines = $content->explode(PHP_EOL);

        // guess csv separator
        $amountComma = $content->substrCount(',');
        $amountSemicolon = $content->substrCount(';');
        $separator = $amountSemicolon > $amountComma ? ';' : ',';

        // extract header and data, explode data with csv separator guesses above
        $header = str_getcsv((string) $lines->first(), $separator, escape: '\\');
        $data = $lines->except(0)
            // reject fully empty lines and lines with only separators inside
            ->reject(fn ($line): bool => empty($line) || Regex::match('/^(,*|;*)\r?\n?$/', $line)->hasMatch())
            // transform csv lines to array
            ->map(fn ($line) => str_getcsv((string) $line, $separator, escape: ''))
            ->map(function ($lineArray) {
                // normalize data
                foreach ($lineArray as $key => $cell) {
                    // tests
                    $moneyTest = Regex::match('/^(\-?)(\d+)([,\.](\d{1,2}))?$/', $cell);
                    $dateTest = Regex::match('/^([0-3]?\d)\.([01]?\d)\.((20)?\d{2})$/', $cell);
                    // conversions
                    if ($moneyTest->hasMatch()) {
                        // normalize money
                        $g = $moneyTest->groups();
                        $lineArray[$key] = $g[1] // sign
                            .Str::padRight($g[2] ?? '', 1, '0') //  money before delimiter (at least 1 digit)
                            .'.' // delimiter (3rd group, with the rest together)
                            .Str::padRight($g[4] ?? '', 2, '0'); // cents after delimiter (at least 2 digits)
                    } elseif ($dateTest->hasMatch()) {
                        // normalize dates
                        $g = $dateTest->groups();
                        $lineArray[$key] = Str::padLeft($g[3], 4, '20') // year
                            .'-'.Str::padLeft($g[2], 2, '0')
                            .'-'.Str::padLeft($g[1], 2, '0');
                    }
                }

                return $lineArray;
            });

        return ['header' => $header, 'data' => $data, 'separator' => $separator];
    }
}
