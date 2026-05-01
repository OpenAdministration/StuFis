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
        Schema::create('tax_budget', function (Blueprint $table) {
            $table->id();
            $table->integer('hhp_id'); // replace with new hhp later :)
            $table->integer('titel_id'); // replace with new hhp later :)
            $table->decimal('tax_percent');
            $table->timestamps();

            $table->foreign('hhp_id')->references('id')->on('haushaltsplan');
            $table->foreign('titel_id')->references('id')->on('haushaltstitel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
