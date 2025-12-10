<?php

namespace App\States\Project;

class NeedOrgApproval extends ProjectState
{
    public static string $name = 'need-stura';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-hourglass-half';

    }

    #[\Override]
    public function color(): string
    {
        return 'yellow';
    }
}
