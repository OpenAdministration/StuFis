<?php

use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist each project's budget plan link, previously derived from createdat
     * on every read (see Project::relatedBudgetPlan).
     */
    public function up(): void
    {
        // Nullable: projects created before the earliest plan stay null.
        Schema::table('projekte', function (Blueprint $table) {
            $table->integer('hhp_id')->nullable();
            $table->foreign('hhp_id')->references('id')->on('haushaltsplan');
        });

        // Plans partition time without overlap or gaps, so one UPDATE per plan
        // assigns each project to the plan whose [von, bis] contains its createdat.
        foreach (LegacyBudgetPlan::all() as $plan) {
            DB::table('projekte')
                ->where('createdat', '>=', $plan->von)
                // Open-ended latest plan has bis = null; `bis` is a date, so
                // extend to end-of-day to include the whole final day.
                ->when($plan->bis !== null,
                    fn ($q) => $q->where('createdat', '<=', $plan->bis->copy()->endOfDay()))
                ->update(['hhp_id' => $plan->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->dropForeign(['hhp_id']);
            $table->dropColumn('hhp_id');
        });
    }
};
