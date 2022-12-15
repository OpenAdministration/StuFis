<?php

namespace App\Models\Enums;

enum BudgetPlanState: string
{
    case FINAL = 'final';
    case DRAFT = 'draft';
}
