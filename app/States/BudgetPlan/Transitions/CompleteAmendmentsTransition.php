<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Completed;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\DefaultTransition;

/**
 * The Active -> Completed transition for a budget_plan. An amendment must only move Active <->
 * Completed in lockstep with the original plan it was applied to (OP#589 F8) — never on its own,
 * see BudgetPlanPolicy::transitionTo, which refuses that arc for an amendment reached directly.
 * This class is where the "in lockstep" half lives: when the model is an original (non-amendment)
 * plan, every one of its amendments that is currently Active follows it to Completed. An amendment
 * still in Draft/Resolved/Approved was never applied to the live plan, so it is left untouched —
 * there is no cascade policy for it here.
 *
 * When the model IS an amendment (its own arc, reached only via this cascade — the policy blocks
 * the direct route), there is nothing further to cascade: an amendment has no amendments of its
 * own, so this is just the ordinary state write.
 *
 * The parent's own state write and every cascaded child write happen in ONE DB transaction, so a
 * failure part-way (e.g. a child that unexpectedly can't be saved) leaves parent and amendments
 * unchanged rather than disagreeing.
 *
 * Note: cascading here calls $child->state->transitionTo() directly against the model, which does
 * NOT run BudgetPlanPolicy — only the Livewire ⚡plan-view::changeState() authorizes before
 * transitioning (see Spatie\ModelStates\Transition / DefaultTransition, which never invoke Gate).
 * So this cascade does not trip the individual-move guard added for 8b.
 */
class CompleteAmendmentsTransition extends DefaultTransition
{
    #[\Override]
    public function handle()
    {
        return DB::transaction(function () {
            /** @var BudgetPlan $model */
            $model = $this->model;
            $result = parent::handle();

            if (! $model->isAmendment()) {
                foreach ($model->amendments()->where('state', Active::$name)->get() as $amendment) {
                    $amendment->state->transitionTo(Completed::class);
                }
            }

            return $result;
        });
    }
}
