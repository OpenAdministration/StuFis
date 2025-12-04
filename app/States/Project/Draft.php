<?php

namespace App\States\Project;

use App\Models\User;
use App\States\Project\ProjectState;

class Draft extends ProjectState
{

    public static string $name = 'draft';

    public function iconName() : string
    {
        return 'fas-file-pen';

    }
    public function color() : string {
        return 'zinc';
    }
}
