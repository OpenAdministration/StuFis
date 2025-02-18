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
        Schema::table('comments', function (Blueprint $table) {
            // create legacy is already changed, this is not for new setups but for upgrading instances < 4.3.x
            $table->text('text')->change();
        });
        Schema::dropIfExists('password_resets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
