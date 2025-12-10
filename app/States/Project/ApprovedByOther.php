<?php

namespace App\States\Project;

class ApprovedByOther extends ProjectState
{
    public static string $name = 'done-other';

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
