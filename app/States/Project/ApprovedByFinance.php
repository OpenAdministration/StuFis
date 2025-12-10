<?php

namespace App\States\Project;

class ApprovedByFinance extends ProjectState
{
    public static string $name = 'done-hv';

    #[\Override]
    public function expensable(): bool
    {
        return true;
    }

    #[\Override]
    public function iconName(): string
    {
        return 'fas-scroll';

    }

    #[\Override]
    public function color(): string
    {
        return 'green';
    }
}
