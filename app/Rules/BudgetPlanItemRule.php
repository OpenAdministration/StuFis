<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates one budget_item row (as gathered by BudgetPlanState::itemsForValidation()) against
 * the three OP#584 business rules a plan's items must satisfy before it may advance along its
 * workflow:
 *
 *  - short_name (Titelnummer) must be unique within the checked scope
 *  - name must not be empty
 *  - value must not be negative
 *
 * Applied to the whole 'items.*' element (not a single field) rather than one rule per field, so
 * every failure message can name the offending Titel via its own short_name (falling back to its
 * id) — a bare "the items.3.value field must be at least 0" wouldn't tell a budget officer which
 * Titel to go fix.
 */
class BudgetPlanItemRule implements DataAwareRule, ValidationRule
{
    /** @var array{items?: list<array{id: int, short_name: ?string, name: ?string, value: int}>} */
    private array $data = [];

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $label = filled($value['short_name'] ?? null) ? $value['short_name'] : '#'.($value['id'] ?? '?');

        if (blank($value['name'] ?? null)) {
            $fail(__('budget-plan.validation.empty-name', ['titel' => $label]));
        }

        if ((int) ($value['value'] ?? 0) < 0) {
            $fail(__('budget-plan.validation.negative-value', ['titel' => $label]));
        }

        // a blank short_name never counts as "taken" (mirrors BudgetPlan::organizationTaken()) —
        // two groups/items without one yet must not spuriously collide with each other
        if (filled($value['short_name'] ?? null)) {
            $duplicateCount = collect($this->data['items'] ?? [])
                ->filter(fn (array $item): bool => ($item['short_name'] ?? null) === $value['short_name'])
                ->count();

            if ($duplicateCount > 1) {
                $fail(__('budget-plan.validation.duplicate-short-name', ['titel' => $value['short_name']]));
            }
        }
    }
}
