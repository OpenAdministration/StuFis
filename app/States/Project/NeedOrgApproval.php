<?php

namespace App\States\Project;

class NeedOrgApproval extends ProjectState
{
    public static string $name = 'need-stura';

    public function iconName(): string
    {
        return 'fas-hourglass-half';

    }

    public function color(): string
    {
        return 'yellow';
    }
}
