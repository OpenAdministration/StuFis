<?php

namespace App\States\Project;

use App\States\Project\ProjectState;

class Applied extends ProjectState
{

    public static string $name = 'wip';

    public function iconName() : string
    {
        return 'fas-paper-plane';

    }
    public function color() : string {
        return 'sky';
    }
}
