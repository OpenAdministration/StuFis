<?php

namespace App\States\BudgetPlan;

class Completed extends BudgetPlanState
{
    public static string $name = 'completed';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-flag-checkered';
    }

    #[\Override]
    public function color(): string
    {
        return 'indigo';
    }
}
