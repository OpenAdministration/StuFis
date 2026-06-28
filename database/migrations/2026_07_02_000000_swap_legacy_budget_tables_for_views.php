<?php

use App\Support\Budget\BudgetPlanConverter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Discontinue the legacy budget plan (haushaltsplan/haushaltsgruppen/haushaltstitel) by turning
 * those tables into read-only VIEWS over the new budget_plan/budget_item structure, so the legacy
 * PHP app keeps reading live data while the new budget plan becomes the single source of truth.
 *
 * Steps: convert any existing legacy data into the new tables (idempotent — preserves leaf ids),
 * re-point the booking/projektposten/tax_budget foreign keys from the legacy tables to budget_item
 * /budget_plan, drop the legacy tables and recreate them as views.
 *
 * NOT data-reversible: down() restores the structure (empty tables + views removed) but not the
 * data. Roll back a production upgrade from a backup, using the fingerprint from
 * `legacy:verify-booking-migration` to confirm the booking table was preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Convert existing legacy plans into budget_plan/budget_item (no-op on a fresh/empty DB).
        (new BudgetPlanConverter)->convert();

        // 2. Drop every FK that references the legacy tables (constraint names vary by environment,
        //    so discover them instead of guessing). This also removes the legacy tables' internal
        //    FKs, freeing them to be dropped.
        $this->dropForeignKeysReferencing(['haushaltstitel', 'haushaltsgruppen', 'haushaltsplan']);

        // 3. Drop the legacy tables (children first).
        Schema::dropIfExists('haushaltstitel');
        Schema::dropIfExists('haushaltsgruppen');
        Schema::dropIfExists('haushaltsplan');

        // 4. Re-point the accounting foreign keys at the new tables. titel_id == budget_item.id for
        //    the preserved leaves; tax_budget.hhp_id == budget_plan.id. The legacy columns are
        //    signed int(11); widen them to unsigned bigint to match the new keys before the FK.
        Schema::table('booking', function (Blueprint $t): void {
            $t->unsignedBigInteger('titel_id')->change();
            $t->foreign('titel_id')->references('id')->on('budget_item');
        });
        Schema::table('projektposten', function (Blueprint $t): void {
            $t->unsignedBigInteger('titel_id')->nullable()->change();
            $t->foreign('titel_id')->references('id')->on('budget_item');
        });
        Schema::table('tax_budget', function (Blueprint $t): void {
            $t->unsignedBigInteger('titel_id')->change();
            $t->renameColumn('titel_id', 'budget_id');

            $t->unsignedBigInteger('hhp_id')->change();
            $t->renameColumn('hhp_id', 'plan_id');

            $t->foreign('budget_id')->references('id')->on('budget_item');
            $t->foreign('plan_id')->references('id')->on('budget_plan');
        });

        // 5. Recreate the legacy names as views projecting the new structure. Mounts are excluded
        //    (they have no legacy equivalent). A deeply-nested new plan flattens — fine for booking,
        //    which only ever targets leaves.
        $this->createViews();
    }

    public function down(): void
    {
        $p = DB::getTablePrefix();
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltstitel`");
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltsgruppen`");
        DB::statement("DROP VIEW IF EXISTS `{$p}haushaltsplan`");

        // Drop the re-pointed FKs before recreating the legacy tables.
        $this->dropForeignKeysReferencing(['budget_item', 'budget_plan'], onlyOn: ['booking', 'projektposten', 'tax_budget']);

        Schema::create('haushaltsplan', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->date('von')->nullable();
            $table->date('bis')->nullable();
            $table->string('state', 64)->nullable();
        });
        Schema::create('haushaltsgruppen', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('hhp_id')->index('hhp_id');
            $table->string('gruppen_name', 128)->nullable();
            $table->tinyInteger('type')->nullable();
        });
        Schema::create('haushaltstitel', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('hhpgruppen_id')->index('hhpgruppen_id');
            $table->string('titel_name', 128)->nullable();
            $table->string('titel_nr', 10)->nullable();
            $table->decimal('value', 12, 2)->nullable();
        });

        // Narrow the columns back to the legacy signed int(11) so they match the recreated tables,
        // then re-point the FKs without validating existing rows (the data is gone — this only
        // restores the schema shape).
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('booking', function (Blueprint $t): void {
                $t->integer('titel_id')->change();
                $t->foreign('titel_id')->references('id')->on('haushaltstitel');
            });
            Schema::table('projektposten', function (Blueprint $t): void {
                $t->integer('titel_id')->nullable()->change();
                $t->foreign('titel_id')->references('id')->on('haushaltstitel');
            });
            Schema::table('tax_budget', function (Blueprint $t): void {
                $t->integer('budget_id')->change();
                $t->renameColumn('budget_id', 'titel_id');
                $t->integer('plan_id')->change();
                $t->renameColumn('plan_id', 'hhp_id');
                $t->foreign('titel_id')->references('id')->on('haushaltstitel');
                $t->foreign('hhp_id')->references('id')->on('haushaltsplan');
            });
            Schema::table('haushaltsgruppen', fn (Blueprint $t) => $t->foreign('hhp_id')->references('id')->on('haushaltsplan'));
            Schema::table('haushaltstitel', fn (Blueprint $t) => $t->foreign('hhpgruppen_id')->references('id')->on('haushaltsgruppen'));
        });
    }

    /**
     * Drop all foreign keys that reference any of $referencedTables. When $onlyOn is given, only
     * drop FKs that live on those tables. Table names are matched with the connection prefix, and
     * the query is raw so the prefix isn't (wrongly) applied to the information_schema table.
     *
     * @param  list<string>  $referencedTables
     * @param  list<string>|null  $onlyOn
     */
    private function dropForeignKeysReferencing(array $referencedTables, ?array $onlyOn = null): void
    {
        $prefix = DB::getTablePrefix();
        $referenced = array_map(fn (string $t): string => $prefix.$t, $referencedTables);

        $sql = 'SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IN ('.$this->placeholders($referenced).')';
        $bindings = $referenced;

        if ($onlyOn !== null) {
            $on = array_map(fn (string $t): string => $prefix.$t, $onlyOn);
            $sql .= ' AND TABLE_NAME IN ('.$this->placeholders($on).')';
            $bindings = array_merge($bindings, $on);
        }

        foreach (DB::select($sql, $bindings) as $fk) {
            DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    /**
     * @param  list<string>  $values
     */
    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }

    private function createViews(): void
    {
        $p = DB::getTablePrefix();

        DB::statement(
            // INNER JOIN: a plan without a fiscal year has no legacy von/bis representation, and
            // the legacy code assumes those are set — so such (draft) plans are not exposed.
            "CREATE VIEW `{$p}haushaltsplan` AS
             SELECT bp.id AS id,
                    fy.start_date AS von,
                    fy.end_date AS bis,
                    CASE WHEN bp.state IN ('published', 'completed') THEN 'final' ELSE 'draft' END AS state
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
             WHERE bi.is_group = 1 AND bi.referenced_plan_id IS NULL"
        );

        DB::statement(
            "CREATE VIEW `{$p}haushaltstitel` AS
             SELECT bi.id AS id,
                    bi.parent_id AS hhpgruppen_id,
                    bi.name AS titel_name,
                    bi.short_name AS titel_nr,
                    bi.value AS value
             FROM `{$p}budget_item` bi
             WHERE bi.is_group = 0 AND bi.referenced_plan_id IS NULL"
        );
    }
};
