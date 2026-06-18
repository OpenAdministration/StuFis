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
        Schema::create('projekt_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('projekt_id');

            $table->unsignedInteger('size');
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->timestamps();

            $table->foreign('projekt_id')->references('id')->on('projekte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projekt_attachments');
    }
};
