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

    /**
     * Active -> Completed (the only forward arc reaching this state — OP#584) is a
     * bookkeeping-period toggle, not a data edit: an already-Active plan must never get stuck
     * un-transitionable over legacy item data it cannot fix, so this arc is deliberately left
     * unchecked. Completed -> Active (reactivation) is a separate, backward arc — it's already
     * exempt via BudgetPlanState::advancesTo() regardless of what this method returns, so this
     * override is not what protects that direction.
     */
    #[\Override]
    public function itemRules(): array
    {
        return [];
    }
}
