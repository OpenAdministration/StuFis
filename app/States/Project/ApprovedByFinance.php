<?php

namespace App\States\Project;

class ApprovedByFinance extends ProjectState
{
    public static string $name = 'done-hv';

    #[\Override]
    public function expensable(): bool
    {
        return true;
    }

    #[\Override]
    public function iconName(): string
    {
        return 'fas-scroll';

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
