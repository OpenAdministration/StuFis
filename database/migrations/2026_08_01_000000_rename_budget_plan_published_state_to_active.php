<?php

use App\Models\BudgetPlan;
use Illuminate\Database\Migrations\Migration;

/**
 * Renames the BudgetPlan state `published` -> `active` (App\States\BudgetPlan\Published ->
 * Active). Purely a data migration for the `state` column value; goes through the model/query
 * builder (not raw SQL) so the connection's table prefix is respected automatically.
 *
 * The legacy `haushaltsplan` view maps `state = 'draft' ? 'draft' : 'final'`, so this rename does
 * not affect legacy readers.
 */
return new class extends Migration
{
    public function up(): void
    {
        BudgetPlan::query()->where('state', 'published')->update(['state' => 'active']);
    }

    public function down(): void
    {
        BudgetPlan::query()->where('state', 'active')->update(['state' => 'published']);
    }
};
