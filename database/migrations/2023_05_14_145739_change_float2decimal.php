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
        Schema::table('projektposten', static function (Blueprint $table){
            $table->decimal('einnahmen', 12, 2)->change();
            $table->decimal('ausgaben', 12, 2)->change();
        });

        Schema::table('konto', static function (Blueprint $table){
            $table->decimal('value', 12, 2)->change();
        });

        Schema::table('konto', static function (Blueprint $table){
            $table->decimal('value', 12, 2)->change();
        });

        Schema::table('haushaltstitel', static function (Blueprint $table){
            $table->decimal('value', 12, 2)->change();
        });

        Schema::table('booking', static function (Blueprint $table){
            $table->decimal('value', 12, 2)->change();
        });

        Schema::table('beleg_posten', static function (Blueprint $table){
            $table->decimal('einnahmen', 12, 2)->default(0)->change();
            $table->decimal('ausgaben', 12, 2)->default(0)->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // why?
    }
};
