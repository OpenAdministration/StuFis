<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->renameColumn('recht-additional', 'recht_additional');
            $table->renameColumn('date-start', 'date_start');
            $table->renameColumn('date-end', 'date_end');
            $table->renameColumn('org-mail', 'org_mail');
        });

        Schema::table('auslagen', function (Blueprint $table) {
            $table->renameColumn('ok-hv', 'ok_hv');
            $table->renameColumn('ok-kv', 'ok_kv');
            $table->renameColumn('ok-belege', 'ok_belege');
            $table->renameColumn('zahlung-iban', 'zahlung_iban');
            $table->renameColumn('zahlung-name', 'zahlung_name');
            $table->renameColumn('zahlung-vwzk', 'zahlung_vwzk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->renameColumn('recht_additional', 'recht-additional');
            $table->renameColumn('date_start', 'date-start');
            $table->renameColumn('date_end', 'date-end');
            $table->renameColumn('org_mail', 'org-mail');
        });

        Schema::table('auslagen', function (Blueprint $table) {
            $table->renameColumn('ok_hv', 'ok-hv');
            $table->renameColumn('ok_kv', 'ok-kv');
            $table->renameColumn('ok_belege', 'ok-belege');
            $table->renameColumn('zahlung_iban', 'zahlung-iban');
            $table->renameColumn('zahlung_name', 'zahlung-name');
            $table->renameColumn('zahlung_vwzk', 'zahlung-vwzk');
        });
    }
};
