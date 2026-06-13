<?php

namespace App\States\Project;

class NeedFinanceApproval extends ProjectState
{
    public static string $name = 'ok-by-hv';

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
