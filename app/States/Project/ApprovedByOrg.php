<?php

namespace App\States\Project;

class ApprovedByOrg extends ProjectState
{
    public static string $name = 'ok-by-stura';

    #[\Override]
    public function expensable(): bool
    {
        return true;
    }

    #[\Override]
    public function iconName(): string
    {
        return 'fas-check';

    }

    #[\Override]
    public function color(): string
    {
        return 'green';
    }

    public function rules(): array
    {
        return parent::rules() + [
            'recht' => 'required|string',
            'recht-additional' => 'sometimes|nullable|string',
            'posts.*.titel_id' => 'sometimes|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
        ];
    }

}
