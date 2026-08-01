<?php

namespace App\Support\Budget;

use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use Cknow\Money\Money;

/**
 * Aggregates an amendment's net income/expense delta from its change rows (F5, OP#581) — how much
 * the Nachtrag adds/removes in sum, plus the resulting saldo shift. Computed once here and
 * rendered in both the editor's Begründungen tab and the amendment's plan-view diff section.
 *
 * Only LEAF items ever contribute: a group's value is always derived (the live sum of its
 * children), never stored/changed directly, so counting it too would double-count every leaf
 * underneath it — e.g. addGroup() records an `add` change for the new group AND its first leaf
 * child; only the leaf's value may be counted.
 */
class AmendmentDeltaSummary
{
    /** @return array{income: Money, expense: Money, saldo: Money} */
    public function compute(BudgetPlan $amendment): array
    {
        $income = Money::EUR(0);
        $expense = Money::EUR(0);

        $changes = $amendment->itemChanges()->with('budgetItem')->get();
        foreach ($changes as $change) {
            $item = $change->budgetItem;
            if ($item === null || $item->is_group) {
                continue;
            }

            $delta = match ($change->action) {
                BudgetItemChange::ACTION_MODIFY => $this->modifyDelta($change),
                BudgetItemChange::ACTION_ADD => $item->value ?? Money::EUR(0),
                BudgetItemChange::ACTION_DELETE => Money::EUR(0)->subtract($item->value ?? Money::EUR(0)),
                default => Money::EUR(0),
            };

            if ($item->budget_type === BudgetType::INCOME) {
                $income = $income->add($delta);
            } else {
                $expense = $expense->add($delta);
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'saldo' => $income->subtract($expense),
        ];
    }

    /** `to - from` for a modify change's `value` field, or 0 when this change doesn't touch value at all. */
    private function modifyDelta(BudgetItemChange $change): Money
    {
        $pair = $change->fieldChange('value');
        if ($pair === null) {
            return Money::EUR(0);
        }

        return Money::EUR((int) $pair['to'])->subtract(Money::EUR((int) $pair['from']));
    }
}
