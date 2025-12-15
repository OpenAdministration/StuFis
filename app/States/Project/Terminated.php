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

    #[\Override]
    public function rules(): array
    {
        return parent::rules() + [
            'recht' => 'required|string',
            'recht-additional' => 'sometimes|nullable|string',
            'posts.*.titel_id' => 'sometimes|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
        ];
    }
}
