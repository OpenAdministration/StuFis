<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `recht_additional` holds the free text next to the legal basis ("Datum und
     * TOP"). 128 characters are not enough to list several committee decisions,
     * and because the productive connection runs without strict mode MariaDB
     * truncated the surplus silently instead of complaining.
     */
    public function up(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->string('recht_additional', 512)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->string('recht_additional', 128)->nullable()->change();
        });
    }
};
