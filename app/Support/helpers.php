<?php

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\UploadedFile;
use League\CommonMark\Output\RenderedContentInterface;
use Spatie\Regex\Regex;

/**
 * Guess the format of the input date because banks do not like standards :(
 */
function guessDate(string $dateString, ?string $newFormat = 'Y-m-d'): string
{
    $formats = ['d.m.y', 'd.m.Y', 'y-m-d', 'Y-m-d', 'jmy', 'jmY', 'dmy', 'dmY'];
    foreach ($formats as $format) {
        try {
            $ret = Date::rawCreateFromFormat($format, $dateString);
        } catch (InvalidFormatException) {
            continue;
        }
        // if successfully parsed
        if ($newFormat !== '' && $newFormat !== null) {
            return $ret->format($newFormat);
        }

        return $ret;
    }
    throw new InvalidFormatException("$dateString is not a valid date");
}

function guessEncoding($path_to_file): string
{
    $finfo = finfo_open(FILEINFO_MIME);
    $fileinfo = finfo_file($finfo, $path_to_file);
    $encoding = Regex::match("/charset=(\S+)/", $fileinfo)->group(1);
    finfo_close($finfo);

    return $encoding;
}

function utf8Content(UploadedFile $file): string
{
    $content = $file->getContent();

    // Already valid UTF-8 → leave it untouched (keeps existing UTF-8 exports correct).
    // We deliberately do NOT trust finfo's charset guess here: finfo only samples the
    // first ~64 KB of a file, so on a large bank statement an umlaut past that window
    // makes it report "us-ascii" and a strict iconv() would throw on that very byte.
    if (mb_check_encoding($content, 'UTF-8')) {
        return $content;
    }

    // Otherwise assume Windows-1252 (the dominant German bank-export encoding, a
    // superset of ISO-8859-1). mb_convert_encoding never throws and preserves umlauts.
    return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
}

function money_format(float|int|string $value): string
{
    return number_format($value, 2, ',', '.')."\u{00A0}€";
}

function markdownToHtml($markdown): RenderedContentInterface
{
    return app()->get('markdown.converter')->convert($markdown);
}
