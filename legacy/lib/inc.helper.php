<?php

use framework\baseclass\TextStyle;

function generateLinkFromID($text, $dest, $linkColor = TextStyle::__default): string
{
    return "<a class='$linkColor' href='".htmlspecialchars(URIBASE.$dest)."'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
}

function generateLinkFromRoute($text, $url, $linkColor = TextStyle::__default): string
{
    return "<a class='$linkColor' href='".htmlspecialchars($url)."'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
}
