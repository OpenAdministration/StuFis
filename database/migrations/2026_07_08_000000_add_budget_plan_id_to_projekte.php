<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persist each project's budget plan as an explicit foreign key instead of deriving it from
 * `createdat` on every read (the old `Project::relatedBudgetPlan()` / `findByDate()` scheme).
 *
 * That date-based scheme relied on every point in time falling inside exactly one plan's
 * [von, bis] range. The 4.5.0 rework exposes `haushaltsplan.von`/`bis` from the fiscal year, so
 * several budget plans in the same fiscal year now share an identical date range and can no longer
 * be told apart by date. A stored reference is the robust replacement.
 *
 * Runs after the legacy tables became views (2026_07_02), so `budget_plan`/`fiscal_year` exist and
 * are populated. Backfill is still date-based, but that is unambiguous for existing data: a clean
 * upgrade converts the legacy plans 1:1, leaving one plan per fiscal year.
 *
 * Also modernises the legacy timestamp columns `createdat`/`lastupdated` to Laravel's conventional
 * `created_at`/`updated_at` (a rename, so data is preserved). The legacy PHP app was updated to the
 * new names in lock-step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projekte', function (Blueprint $table): void {
            // Nullable: projects created before the earliest plan have no covering plan. No
            // ON DELETE action (RESTRICT): a plan that still owns projects must not be deleted,
            // rather than silently orphaning them.
            $table->foreignId('budget_plan_id')->nullable()->after('id')
                ->constrained('budget_plan');
        });

        // Assign each project to the plan whose fiscal year contains its createdat. Ordered by id so
        // the result is deterministic if a fiscal year already has more than one plan (highest wins).
        $plans = DB::table('budget_plan')
            ->join('fiscal_year', 'fiscal_year.id', '=', 'budget_plan.fiscal_year_id')
            ->orderBy('budget_plan.id')
            ->get(['budget_plan.id', 'fiscal_year.start_date AS von', 'fiscal_year.end_date AS bis']);

        foreach ($plans as $plan) {
            DB::table('projekte')
                ->where('createdat', '>=', $plan->von)
                // `bis` is a date; extend to end-of-day to include the whole final day.
                ->when($plan->bis !== null,
                    fn ($q) => $q->where('createdat', '<=', Carbon::parse($plan->bis)->endOfDay()))
                ->update(['budget_plan_id' => $plan->id]);
        }

        // Rename the legacy timestamp columns to Laravel's conventional names (kept as a separate
        // blueprint so the rename isn't mixed with other column ops).
        Schema::table('projekte', function (Blueprint $table): void {
            $table->renameColumn('createdat', 'created_at');
            $table->renameColumn('lastupdated', 'updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('projekte', function (Blueprint $table): void {
            $table->renameColumn('created_at', 'createdat');
            $table->renameColumn('updated_at', 'lastupdated');
        });

        Schema::table('projekte', function (Blueprint $table): void {
            $table->dropForeign(['budget_plan_id']);
            $table->dropColumn('budget_plan_id');
        });
    }
};
