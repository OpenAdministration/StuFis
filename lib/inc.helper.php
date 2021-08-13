<?php

use framework\baseclass\TextStyle;

function generateLinkFromID($text, $dest, $linkColor = TextStyle::__default): string
{
    return "<a class='$linkColor' href='" . htmlspecialchars(URIBASE . $dest) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
}



if (!function_exists('generateRandomString')){
    /**
     * generates secure random hex string of length: 2*$length
     * @param integer $length 0.5 string length
     * @return string
     * @throws Exception
     */
	function generateRandomString(int $length) : string
    {
        return bin2hex(random_bytes($length));
	}
}

