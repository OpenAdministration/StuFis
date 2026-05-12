<?php

namespace App\Models\Enums;

enum BudgetPlanState: string
{
    case FINAL = 'final';
    case DRAFT = 'draft';

    public function slug(): string
    {
        return match ($this) {
            self::FINAL => 'final',
            self::DRAFT => 'draft',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FINAL => __('Final'),
            self::DRAFT => __('Draft'),
        };
    }
}
