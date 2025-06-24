<?php

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\UploadedFile;
use Spatie\Regex\Regex;

if (! function_exists('guessCarbon')) {
    /**
     * Guess the format of the input date because banks do not like standards :(
     */
    function guessCarbon(string $dateString, ?string $newFormat = null): Carbon|string
    {
        $formats = ['d.m.y', 'd.m.Y', 'y-m-d', 'Y-m-d', 'jmy', 'jmY', 'dmy', 'dmY'];
        foreach ($formats as $format) {
            try {
                $ret = Carbon::rawCreateFromFormat($format, $dateString);
            } catch (InvalidFormatException) {
                continue;
            }
            // if successfully parsed
            if ($newFormat) {
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
        $enc = guessEncoding($file->getPathname());

        return iconv($enc, 'UTF-8', $file->getContent());
    }
}
