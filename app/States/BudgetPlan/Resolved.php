<?php

namespace App\States\BudgetPlan;

class Resolved extends BudgetPlanState
{
    public static string $name = 'resolved';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-gavel';
    }

    #[\Override]
    public function color(): string
    {
        return 'sky';
    }
}
