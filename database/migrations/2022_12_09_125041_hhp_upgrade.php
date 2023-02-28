<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('budget_plan', static function (Blueprint $table){
            $table->id();
            $table->string('organisation', 64);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('resolution_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('state', 32);
            $table->unsignedBigInteger('parent_plan_id')->nullable();
            $table->foreign('parent_plan_id')->references('id')->on('budget_plan');
            $table->timestamps();
        });

        Schema::create('budget_item', static function (Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('budget_plan_id');
            $table->string('short_name', 16);
            $table->string('name');
            $table->integer('value');
            $table->integer('budget_type');
            $table->text('description');
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->unique(['budget_plan_id', 'short_name']);
            $table->foreign('budget_plan_id')->references('id')->on('budget_plan');
            $table->foreign('parent_id')->references('id')->on('budget_item');
            //$table->text('diff_description');
        });
    }

    public function down()
    {
        Schema::table('budget_item', static function (Blueprint $table) {
            $table->dropForeign(['budget_plan_id']);
        });
        Schema::dropIfExists('budget_plan');
        Schema::dropIfExists('budget_item');
    }

};
