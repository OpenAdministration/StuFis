<?php

namespace App\States\Project;

class Applied extends ProjectState
{
    public static string $name = 'wip';

    public function iconName(): string
    {
        return 'fas-paper-plane';

    }

    public function color(): string
    {
        return 'sky';
    }
}
