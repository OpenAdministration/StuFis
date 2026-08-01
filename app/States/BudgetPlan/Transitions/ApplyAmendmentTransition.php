<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;
use App\Support\Budget\AmendmentApplier;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\DefaultTransition;

/**
 * The Approved -> Active transition for a budget_plan. When the model is a Nachtragshaushaltsplan
 * (amendment), this applies its drafted changes onto the parent plan's live budget_item rows
 * before writing the new state — see AmendmentApplier for the apply semantics. For an original
 * (non-amendment) plan this is just the ordinary state write.
 *
 * Both the apply and the state write happen in one DB transaction, so a conflict during apply()
 * aborts the whole transition: the plan is left in Approved, unchanged.
 */
class ApplyAmendmentTransition extends DefaultTransition
{
    #[\Override]
    public function handle()
    {
        return DB::transaction(function () {
            /** @var BudgetPlan $model */
            $model = $this->model;
            if ($model->isAmendment()) {
                resolve(AmendmentApplier::class)->apply($model);
            }

            return parent::handle();
        });
    }
}
