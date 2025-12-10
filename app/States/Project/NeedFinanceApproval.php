<?php

namespace App\States\Project;

class NeedFinanceApproval extends ProjectState
{
    public static string $name = 'ok-by-hv';

    public function iconName(): string
    {
        return 'fas-hourglass-half';

    }

    public function color(): string
    {
        return 'yellow';
    }
}
