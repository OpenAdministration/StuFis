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
}
