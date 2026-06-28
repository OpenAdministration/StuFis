<?php

namespace App\Models\Enums;

/**
 * What a budget item is. Currently derived (from is_group / referenced_plan_id) rather than
 * stored — see BudgetItem::kind(). A "mount" stands in for the whole income/expense side of
 * another plan.
 */
enum BudgetItemKind: string
{
    case Group = 'group';
    case Budget = 'budget';
    case Mount = 'mount';

    public function icon(): string
    {
        return match ($this) {
            self::Group => 'wallet',
            self::Budget => 'banknotes',
            self::Mount => 'arrow-top-right-on-square',
        };
    }
}
