<?php

namespace App\States\Project;

class Terminated extends ProjectState
{
    public static string $name = 'terminated';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-flag-checkered';

    }

    #[\Override]
    public function color(): string
    {
        return 'zinc';
    }
}
