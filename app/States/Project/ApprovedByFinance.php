<?php

namespace App\States\Project;

use App\States\Project\ProjectState;

class ApprovedByFinance extends ProjectState
{

    public static string $name = 'done-hv';

    public function expensable(): bool
    {
        return true;
    }

    public function iconName() : string
    {
        return 'fas-scroll';

    }
    public function color() : string {
        return 'green';
    }

}
