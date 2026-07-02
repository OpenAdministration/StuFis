<?php

namespace App\Models\Enums;

enum BudgetType: int
{
    case INCOME = 1;
    case EXPENSE = -1;

    public function slug(): string
    {
        return match ($this) {
            self::INCOME => 'in',
            self::EXPENSE => 'out',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::INCOME => __('Income'),
            self::EXPENSE => __('Expense'),
        };
    }

    /**
     * Seed prefix for the auto-generated "Titelnummer" of a first-of-type root
     * item (e.g. E1 / A1). Change here to alter the root prefix.
     */
    public function numberPrefix(): string
    {
        return match ($this) {
            self::INCOME => 'E',
            self::EXPENSE => 'A',
        };
    }

    /** The other side of the budget (income ↔ expense). */
    public function opposite(): self
    {
        return match ($this) {
            self::INCOME => self::EXPENSE,
            self::EXPENSE => self::INCOME,
        };
    }
}
