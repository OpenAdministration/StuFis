<?php

namespace App\Models\Enums;

enum ChatMessageType: int
{
    case SYSTEM = 1;

    case PUBLIC = 0;
    case SUPPORT = 2;
    case FINANCE = 3;

}
