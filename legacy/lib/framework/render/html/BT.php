<?php

namespace framework\render\html;

use framework\baseclass\Enum;

class BT extends Enum
{
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_SECONDARY = 'secondary';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_DANGER = 'danger';
}
