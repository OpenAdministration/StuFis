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
    public function approvalRules(): array
    {
        return [
            'recht' => 'required|string',
            'recht-additional' => 'sometimes|nullable|string',
        ];
    }

    #[\Override]
    public function budgetRules(): array
    {
        return [
            'posts.*.titel_id' => 'required|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
        ];
    }
}
