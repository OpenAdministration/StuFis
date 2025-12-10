<?php

namespace App\States\Project;

class ApprovedByOther extends ProjectState
{
    public static string $name = 'done-other';

    #[\Override]
    public function expensable(): bool
    {
        return true;
    }

    #[\Override]
    public function iconName(): string
    {
        return 'fas-check';

    }

    #[\Override]
    public function color(): string
    {
        return 'green';
    }
}
