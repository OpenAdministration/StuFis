<?php

namespace App\States\Project;

class Revoked extends ProjectState
{
    public static string $name = 'revoked';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-ban';
    }

    #[\Override]
    public function color(): string
    {
        return 'rose';
    }

    #[\Override]
    public function rules(): array
    {
        return [];
    }
}
