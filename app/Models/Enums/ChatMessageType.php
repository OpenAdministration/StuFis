<?php

namespace App\Models\Enums;

enum ChatMessageType: int
{
    case SYSTEM = 0;

    case PUBLIC = 1;
    case SUPPORT = 2;
    case FINANCE = 3;

}
