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

    // No itemRules() override: Draft is rank 0 in BudgetPlanState::order(), so no transition ever
    // advances INTO it — ⚡plan-view::changeState() only checks item rules on a forward step (see
    // BudgetPlanState::advancesTo()), meaning Draft's own itemRules() is simply never consulted.
    // An override here would be dead code, not a second safety net.
}
