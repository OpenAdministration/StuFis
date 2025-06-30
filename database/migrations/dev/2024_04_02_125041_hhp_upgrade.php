<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_year', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
        });

        Schema::create('budget_plan', static function (Blueprint $table) {
            $table->id();
            $table->string('organization', 64)->nullable();
            $table->unsignedBigInteger('fiscal_year_id')->nullable();
            $table->date('resolution_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('state', 32);
            $table->unsignedBigInteger('parent_plan_id')->nullable();

            $table->foreign('parent_plan_id')->references('id')->on('budget_plan');
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_year');
            $table->timestamps();
        });

        Schema::create('budget_item', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_plan_id');
            $table->string('short_name', 16)->nullable();
            $table->string('name')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->integer('budget_type');
            $table->boolean('is_group');
            $table->text('description');
            $table->integer('position')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->foreign('budget_plan_id')->references('id')->on('budget_plan');
            $table->foreign('parent_id')->references('id')->on('budget_item');
            // $table->text('diff_description');
        });

        // TODO: nachtragshhp auch noch hier rein :)
    }

    public function down(): void
    {
        Schema::table('budget_item', static function (Blueprint $table) {
            $table->dropForeign(['budget_plan_id']);
        });
        Schema::table('budget_plan', static function (Blueprint $table) {
            $table->dropForeign(['fiscal_year_id']);
        });

        Schema::dropIfExists('budget_plan');
        Schema::dropIfExists('budget_item');
        Schema::dropIfExists('fiscal_year');
    }
};
