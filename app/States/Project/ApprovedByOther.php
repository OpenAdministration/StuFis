<?php

namespace App\States\Project;

use App\States\Project\ProjectState;

class ApprovedByOther extends ProjectState
{

    public static string $name = 'done-other';

    public function expensable(): bool
    {
        return true;
    }

}
