<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;
use App\Support\Budget\AmendmentApplier;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\DefaultTransition;

/**
 * The Active -> Approved transition for a budget_plan. When the model is an applied amendment,
 * this un-applies its changes from the parent plan's live budget_item rows before writing the new
 * state — the exact inverse of ApplyAmendmentTransition. Also re-defaults activation_date if it's
 * unset (see DefaultsActivationDateOnApproval): a revert can land back on Approved with no date,
 * same as the forward Resolved -> Approved arc. For an original (non-amendment) plan neither of
 * these applies — just the ordinary state write.
 *
 * Atomic like its counterpart: a conflict during revert() aborts the whole transition, leaving
 * the plan in Active, unchanged.
 */
class RevertAmendmentTransition extends DefaultTransition
{
    use DefaultsActivationDateOnApproval;

    #[\Override]
    public function handle()
    {
        return DB::transaction(function () {
            /** @var BudgetPlan $model */
            $model = $this->model;
            if ($model->isAmendment()) {
                resolve(AmendmentApplier::class)->revert($model);
            }
            $this->defaultActivationDate($model);

            return parent::handle();
        });
    }
}
