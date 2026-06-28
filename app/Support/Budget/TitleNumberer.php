<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Suggests the auto-fill "Titelnummer" (short_name) for a freshly created item by
 * reading the numbering already present in the tree — no configured pattern needed:
 *
 *  - new sibling      → copy the preceding sibling and bump its trailing number (A1.2 → A1.3, E1 → E2)
 *  - first child      → parent's number + separator + "1" (A1 → A1.1)
 *  - first-of-type root → the budget type's seed prefix + "1" (E1 / A1)
 *
 * The result is only a suggestion; the user may override it in the input. Resolve via
 * the container (app(TitleNumberer::class)) so it can be swapped/faked in tests.
 */
class TitleNumberer
{
    private const SEPARATOR = '.';

    public function next(BudgetItem $item): string
    {
        $previous = $this->previousSibling($item);

        // continue whatever scheme the previous sibling uses
        if ($previous !== null && $this->hasNumber($previous->short_name)) {
            return $this->incrementLastNumber($previous->short_name);
        }

        // first child: hang off the parent's number
        if ($item->parent !== null && filled($item->parent->short_name)) {
            return $item->parent->short_name.self::SEPARATOR.'1';
        }

        // first root of its type: nothing to copy, fall back to the seed prefix
        return $item->budget_type->numberPrefix().'1';
    }

    /** The sibling immediately before $item by position (null for the first one). */
    private function previousSibling(BudgetItem $item): ?BudgetItem
    {
        return $this->siblings($item)
            ->where('position', '<', $item->position)
            ->orderByDesc('position')
            ->first();
    }

    private function siblings(BudgetItem $item): Builder
    {
        $query = BudgetItem::query()->whereKeyNot($item->getKey());

        return $item->parent_id !== null
            ? $query->where('parent_id', $item->parent_id)
            : $query->where('budget_plan_id', $item->budget_plan_id)
                ->whereNull('parent_id')
                ->where('budget_type', $item->budget_type);
    }

    private function hasNumber(?string $shortName): bool
    {
        return $shortName !== null && preg_match('/\d/', $shortName) === 1;
    }

    /** Increment the last run of digits in the string, preserving everything around it. */
    private function incrementLastNumber(string $shortName): string
    {
        return preg_replace_callback(
            '/(\d+)(?!.*\d)/',
            static function (array $m): string {
                $next = (string) ((int) $m[1] + 1);

                // keep zero-padding width (07 -> 08), but let it grow on overflow (99 -> 100)
                return str_pad($next, strlen($m[1]), '0', STR_PAD_LEFT);
            },
            $shortName,
            1,
        );
    }
}
