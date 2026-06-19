<?php

namespace App\States\Project;

use App\Models\Legacy\LegacyBudgetItem;
use Illuminate\Validation\Rule;

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

    #[\Override]
    public function approvalRules(): array
    {
        return [
            'recht' => 'required|string',
            'recht_additional' => 'sometimes|nullable|string',
        ];
    }

    #[\Override]
    public function budgetRules(): array
    {
        return [
            'posts.*.titel_id' => ['required', 'integer', Rule::exists(LegacyBudgetItem::class, 'id')],
        ];
    }
}
