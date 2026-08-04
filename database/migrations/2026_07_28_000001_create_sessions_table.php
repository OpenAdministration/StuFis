<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            // OIDC session id from the id_token, mirrored here by
            // App\Extensions\Session\OidcDatabaseSessionHandler so Back-Channel
            // Logout can destroy a session by its `sid`.
            $table->string('oidc_sid')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
