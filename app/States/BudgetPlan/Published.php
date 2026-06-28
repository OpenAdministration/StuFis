<?php

namespace App\States\BudgetPlan;

class Published extends BudgetPlanState
{
    public static string $name = 'published';

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
