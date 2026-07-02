<?php

namespace App\States\BudgetPlan;

class Draft extends BudgetPlanState
{
    public static string $name = 'draft';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-file-pen';
    }

    #[\Override]
    public function color(): string
    {
        return 'zinc';
    }
}
