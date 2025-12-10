<?php

namespace App\States\Project;

class Terminated extends ProjectState
{
    public static string $name = 'terminated';

    public function iconName(): string
    {
        return 'fas-flag-checkered';

    }

    public function color(): string
    {
        return 'zinc';
    }
}
