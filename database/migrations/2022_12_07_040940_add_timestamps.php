<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    private array $tables = ['haushaltsplan', 'haushaltsgruppen', 'haushaltstitel'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $table){
            Schema::table($table, function (Blueprint $table) {
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table){
            Schema::table($table, function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
    }
};
