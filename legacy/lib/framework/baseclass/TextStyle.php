<?php

namespace framework\baseclass;

class TextStyle extends Enum
{
    public const __default = self::NORMAL;

    public const NORMAL = '';
    public const BLACK = 'text-color__black';
    public const SECONDARY = 'text-color__secondary';
    public const PRIMARY = 'text-color__primary';
    public const DANGER = 'text-color__danger';
    public const DANGER_DARK = 'text-color__danger-dark';
    public const BOLD = 'text-bold';
    public const GREEN = 'text-color__green';
}
