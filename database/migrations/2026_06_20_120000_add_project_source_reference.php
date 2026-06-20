<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which project a project was created from (via "copy" or
     * "leftovers"), so a backlink can be shown in the show/edit views.
     */
    public function up(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->integer('source_id')->nullable()->after('id')->index();
            $table->string('source_kind')->nullable()->after('source_id');

            $table->foreign('source_id')->references('id')->on('projekte')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projekte', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
            $table->dropColumn(['source_id', 'source_kind']);
        });
    }
};
