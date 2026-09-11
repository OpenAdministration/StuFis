<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F3 (OP#581): an optional free-text name for a plan, primarily meant for amendments — an original
 * plan is identified well enough by its organization, but a Nachtragshaushaltsplan has none of its
 * own, so without this it can only ever be called "the Nachtrag" everywhere it's listed. See
 * BudgetPlan::label(), which falls back to "Nachtrag vom {created_at}" when unset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_plan', static function (Blueprint $table): void {
            $table->string('name')->nullable()->after('organization');
        });
    }

    public function down(): void
    {
        Schema::table('budget_plan', static function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }
};
