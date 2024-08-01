<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('konto_type', function ($table) {
            $table->json('csv_import_settings')->default('{}');
        });

        Schema::table('konto', function ($table) {
            $table->dropColumn('gvcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('konto_type', function ($table) {
            $table->dropColumn('csv_import_settings');
        });
        Schema::table('konto', function ($table) {
            $table->integer('gvcode')->default(0);
        });
    }
};
