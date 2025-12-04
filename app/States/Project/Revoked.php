<?php

namespace App\States\Project;


class Revoked extends ProjectState
{
    public static string $name = 'revoked';

    public function iconName() : string
    {
        return 'fas-ban';
    }
    public function color() : string {
        return 'rose';
    }
}
