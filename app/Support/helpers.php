<?php

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

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
            } catch (InvalidFormatException $e) {
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
}
