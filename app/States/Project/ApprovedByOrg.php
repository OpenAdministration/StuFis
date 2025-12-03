<?php

namespace App\States\Project;

use App\States\Project\ProjectState;

class ApprovedByOrg extends ProjectState
{

    public static string $name = 'ok-by-stura';

    public function expensable(): bool
    {
        return true;
    }
}
