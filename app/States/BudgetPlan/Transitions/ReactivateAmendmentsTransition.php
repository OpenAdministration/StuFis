<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Completed;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\DefaultTransition;

/**
 * The Completed -> Active transition for a budget_plan — the exact inverse of
 * CompleteAmendmentsTransition (OP#589 F8). When the model is an original (non-amendment) plan,
 * every one of its amendments that is currently Completed comes back to Active alongside it. An
 * amendment that was never applied (still Draft/Resolved/Approved when the plan completed) is
 * left untouched here too, symmetric with the completion side.
 *
 * Same atomicity and policy notes as CompleteAmendmentsTransition: one DB transaction for the
 * parent write plus every cascaded child write, and the cascade calls $child->state->transitionTo()
 * directly, which does not run BudgetPlanPolicy (only ⚡plan-view::changeState() authorizes before
 * transitioning) — so it does not trip the individual-move guard added for 8b.
 */
class ReactivateAmendmentsTransition extends DefaultTransition
{
    #[\Override]
    public function handle()
    {
        return DB::transaction(function () {
            /** @var BudgetPlan $model */
            $model = $this->model;
            $result = parent::handle();

            if (! $model->isAmendment()) {
                foreach ($model->amendments()->whereState('state', Completed::class)->get() as $amendment) {
                    $amendment->state->transitionTo(Active::class);
                }
            }

            return $result;
        });
    }
}
