<?php

namespace App\States\Project;

class ApprovedByOrg extends ProjectState
{
    public static string $name = 'ok-by-stura';

    public function expensable(): bool
    {
        return true;
    }

    public function iconName(): string
    {
        return 'fas-check';

    }

    public function color(): string
    {
        return 'green';
    }
}
