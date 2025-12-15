<?php

namespace App\States\Project;

class ApprovedByOther extends ProjectState
{
    public static string $name = 'done-other';

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
