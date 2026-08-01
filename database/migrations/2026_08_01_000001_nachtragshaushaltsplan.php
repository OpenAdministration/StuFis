<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema for the Nachtragshaushaltsplan (supplementary budget plan / amendment) feature — OP#581.
 *
 * An amendment is a `budget_plan` row with `parent_plan_id` set (column + FK already exist from
 * 2026_07_01_000000_hhp_upgrade). It carries two new plan-level fields (`effective_date`,
 * `justification`) and stores its edits as delta rows in the new `budget_item_change` table
 * rather than as copied items — see App\Support\Budget\AmendmentApplier for how those deltas are
 * applied/reverted onto the live budget_item rows.
 *
 * The three legacy views (haushaltsplan/haushaltsgruppen/haushaltstitel) are recreated with
 * amendment-awareness so the legacy app keeps seeing exactly one plan per period: an amendment
 * itself never appears as its own plan, and its as-yet-unapplied additions/deletions don't leak
 * into the parent plan's legacy view rows. The phantom-group logic is otherwise unchanged from
 * 2026_07_02_000000_swap_legacy_budget_tables_for_views.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_plan', static function (Blueprint $table): void {
            $table->date('effective_date')->nullable()->after('approval_date');
            $table->text('justification')->nullable()->after('effective_date');
        });

        Schema::create('budget_item_change', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('budget_plan_id'); // the amendment plan
            $table->unsignedBigInteger('budget_item_id'); // the (live or added) item touched
            $table->string('action', 16); // modify|add|delete
            $table->json('changes')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('budget_plan_id')->references('id')->on('budget_plan')->cascadeOnDelete();
            $table->foreign('budget_item_id')->references('id')->on('budget_item');
            $table->unique(['budget_plan_id', 'budget_item_id']);
        });

        $this->createViews();
    }

    public function down(): void
    {
        $this->dropViews();
        $this->createLegacyViews();

        Schema::dropIfExists('budget_item_change');

        Schema::table('budget_plan', static function (Blueprint $table): void {
            $table->dropColumn(['effective_date', 'justification']);
        });
    }

    private function dropViews(): void
    {
        $p = DB::getTablePrefix();
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltstitel`");
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltsgruppen`");
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltsplan`");
    }

    private function createViews(): void
    {
        $this->dropViews();
        $p = DB::getTablePrefix();

        // Amendments never surface as their own plan — only original plans do.
        DB::statement(
            "CREATE VIEW `{$p}haushaltsplan` AS
             SELECT bp.id AS id,
                    fy.start_date AS von,
                    fy.end_date AS bis,
                    CASE WHEN bp.state = 'draft' THEN 'draft' ELSE 'final' END AS state
             FROM `{$p}budget_plan` bp
             INNER JOIN `{$p}fiscal_year` fy ON fy.id = bp.fiscal_year_id
             WHERE bp.parent_plan_id IS NULL"
        );

        // Items are excluded while they still belong to an amendment plan (drafted additions not
        // yet applied, or applied deletions parked back under the amendment). Applied additions are
        // re-homed to the parent plan by AmendmentApplier, so they appear here exactly from the
        // moment the amendment goes Active — that is intended.
        DB::statement(
            "CREATE VIEW `{$p}haushaltsgruppen` AS
             SELECT bi.id AS id,
                    bi.budget_plan_id AS hhp_id,
                    bi.name AS gruppen_name,
                    CASE WHEN bi.budget_type = 1 THEN 0 ELSE 1 END AS type
             FROM `{$p}budget_item` bi
             INNER JOIN `{$p}budget_plan` bp ON bp.id = bi.budget_plan_id
             WHERE bi.referenced_plan_id IS NULL
               AND (bi.is_group = 1 OR bi.parent_id IS NULL)
               AND bp.parent_plan_id IS NULL"
        );

        DB::statement(
            "CREATE VIEW `{$p}haushaltstitel` AS
             SELECT bi.id AS id,
                    COALESCE(bi.parent_id, bi.id) AS hhpgruppen_id,
                    bi.name AS titel_name,
                    bi.short_name AS titel_nr,
                    bi.value AS value
             FROM `{$p}budget_item` bi
             INNER JOIN `{$p}budget_plan` bp ON bp.id = bi.budget_plan_id
             WHERE bi.is_group = 0 AND bi.referenced_plan_id IS NULL
               AND bp.parent_plan_id IS NULL"
        );
    }

    /** Restore the pre-amendment view definitions (2026_07_02_..._swap_legacy_budget_tables_for_views). */
    private function createLegacyViews(): void
    {
        $p = DB::getTablePrefix();

        DB::statement(
            "CREATE VIEW `{$p}haushaltsplan` AS
             SELECT bp.id AS id,
                    fy.start_date AS von,
                    fy.end_date AS bis,
                    CASE WHEN bp.state = 'draft' THEN 'draft' ELSE 'final' END AS state
             FROM `{$p}budget_plan` bp
             INNER JOIN `{$p}fiscal_year` fy ON fy.id = bp.fiscal_year_id"
        );

        DB::statement(
            "CREATE VIEW `{$p}haushaltsgruppen` AS
             SELECT bi.id AS id,
                    bi.budget_plan_id AS hhp_id,
                    bi.name AS gruppen_name,
                    CASE WHEN bi.budget_type = 1 THEN 0 ELSE 1 END AS type
             FROM `{$p}budget_item` bi
             WHERE bi.referenced_plan_id IS NULL
               AND (bi.is_group = 1 OR bi.parent_id IS NULL)"
        );

        DB::statement(
            "CREATE VIEW `{$p}haushaltstitel` AS
             SELECT bi.id AS id,
                    COALESCE(bi.parent_id, bi.id) AS hhpgruppen_id,
                    bi.name AS titel_name,
                    bi.short_name AS titel_nr,
                    bi.value AS value
             FROM `{$p}budget_item` bi
             WHERE bi.is_group = 0 AND bi.referenced_plan_id IS NULL"
        );
    }
};
