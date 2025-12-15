<?php

namespace App\States\Project;

class Draft extends ProjectState
{
    public static string $name = 'draft';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-file-pen';

    }

    #[\Override]
    public function color(): string
    {
        return 'zinc';
    }

    #[\Override]
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:128',
        ];
    }
}
