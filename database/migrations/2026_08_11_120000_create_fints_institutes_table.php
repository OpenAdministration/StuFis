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
        Schema::create('fints_institutes', function (Blueprint $table) {
            // The Bankleitzahl is the natural key of the upstream list, always 8 digits, and
            // the key konto_credentials points at - no surrogate id. A BLZ does change on
            // occasion, but then every IBAN at that bank changes with it, which is a manual
            // migration either way.
            $table->char('blz', 8)->primary();

            $table->string('name');
            $table->string('location')->nullable();
            $table->string('bic', 11)->nullable()->index();
            $table->string('checksum_method', 2)->nullable();

            $table->string('rdh_address')->nullable();
            $table->string('pin_tan_address')->nullable();

            // Not numeric: besides "300"/"220" the list also carries ids like "plus".
            $table->string('rdh_version', 16)->nullable();
            $table->string('pin_tan_version', 16)->nullable();

            // Touched on every successful sync, so rows that vanished upstream are the
            // ones left behind with an older stamp, and max(synced_at) is the list date.
            $table->timestamp('synced_at')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fints_institutes');
    }
};
