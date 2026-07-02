<?php

namespace App\States\BudgetPlan;

class Approved extends BudgetPlanState
{
    public static string $name = 'approved';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-circle-check';
    }

    #[\Override]
    public function color(): string
    {
        return 'yellow';
    }
}
