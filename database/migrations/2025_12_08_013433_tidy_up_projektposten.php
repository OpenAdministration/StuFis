<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $db_prefix = config('database.connections.mariadb.prefix', '');
        Schema::table('projektposten', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->change();
        });

        // Store old and new IDs mapping
        DB::statement("
            CREATE TEMPORARY TABLE id_mapping AS
            SELECT pp.id as old_id, t.new_id, pp.projekt_id
            FROM {$db_prefix}projektposten pp
            JOIN (
                SELECT id, projekt_id, ROW_NUMBER() OVER (ORDER BY projekt_id, id) as new_id FROM {$db_prefix}projektposten
            ) as t ON pp.id = t.id AND pp.projekt_id = t.projekt_id
        ");

        // Update projektposten IDs
        DB::statement("
            UPDATE {$db_prefix}projektposten pp
            JOIN id_mapping im ON pp.id = im.old_id and pp.projekt_id = im.projekt_id
            SET pp.id = im.new_id
        ");

        // Update beleg_posten foreign keys
        DB::statement("
            UPDATE {$db_prefix}beleg_posten bp
            JOIN {$db_prefix}belege b ON b.id = bp.beleg_id
            JOIN {$db_prefix}auslagen a ON a.id = b.auslagen_id
            JOIN id_mapping im ON bp.projekt_posten_id = im.old_id AND a.projekt_id = im.projekt_id
            SET bp.projekt_posten_id = im.new_id
        ");

        Schema::table('projektposten', function (Blueprint $table) {
            $table->dropPrimary(['id', 'projekt_id']);
            $table->primary('id')->autoIncrement();
            $table->foreign('titel_id')->references('id')->on('haushaltstitel');
            $table->unsignedInteger('position')->default(1); // for better legacy support with stupid default
        });

        // add missing foreign key
        Schema::table('beleg_posten', function (Blueprint $table) {
            $table->unsignedBigInteger('projekt_posten_id')->change();
            $table->foreign('projekt_posten_id')->references('id')->on('projektposten');
        });

        // Drop temporary table
        DB::statement('DROP TEMPORARY TABLE IF EXISTS id_mapping');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // just accept the fact that every project does not start with 1 as post number
        Schema::table('beleg_posten', function (Blueprint $table) {
            $table->dropForeign(['projekt_posten_id']);
        });
        Schema::table('projektposten', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropForeign(['titel_id']);
            $table->primary(['id', 'projekt_id']);
            $table->dropColumn('position');
        });
    }
};
