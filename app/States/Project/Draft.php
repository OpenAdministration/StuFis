<?php

namespace App\States\Project;

class Draft extends ProjectState
{
    public static string $name = 'draft';

    public function iconName(): string
    {
        return 'fas-file-pen';

    }

    public function color(): string
    {
        return 'zinc';
    }
}
