<?php

namespace App\Models\Enums;

enum BudgetItemChangeAction: string
{
    case Modify = 'modify';
    case Add = 'add';
    case Delete = 'delete';

    public function label(): string
    {
        return __('budget-plan.amendment.change.'.$this->value);
    }

    /** Badge colour for this action — single source of truth for the diff/hint views. */
    public function color(): string
    {
        return match ($this) {
            self::Add => 'green',
            self::Delete => 'red',
            self::Modify => 'amber',
        };
    }
}
