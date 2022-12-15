<?php

namespace App\Models\Enums;

enum BudgetType: int {
    case INCOME = 1;
    case EXPENSE = -1;
}
