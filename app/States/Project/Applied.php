<?php

namespace App\States\Project;

class Applied extends ProjectState
{
    public static string $name = 'wip';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-paper-plane';

    }

    #[\Override]
    public function color(): string
    {
        return 'sky';
    }

    #[\Override]
    public function rules(): array
    {
        return parent::rules();
    }
}
