<?php

namespace App\States\BudgetPlan;

class Active extends BudgetPlanState
{
    public static string $name = 'active';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-bullhorn';
    }

    #[\Override]
    public function color(): string
    {
        return 'green';
    }
}
