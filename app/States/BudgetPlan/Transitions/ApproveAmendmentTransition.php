<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;
use Spatie\ModelStates\DefaultTransition;

/**
 * The Resolved -> Approved transition for a budget_plan. For an amendment, this is also the
 * first arc that can default activation_date to approval_date (see
 * DefaultsActivationDateOnApproval) — the other is RevertAmendmentTransition, reached when an
 * already-applied amendment comes back to Approved. For an original plan there's nothing
 * amendment-specific to do here.
 */
class ApproveAmendmentTransition extends DefaultTransition
{
    use DefaultsActivationDateOnApproval;

    #[\Override]
    public function handle()
    {
        /** @var BudgetPlan $model */
        $model = $this->model;
        $this->defaultActivationDate($model);

        return parent::handle();
    }
}
