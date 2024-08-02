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
        Schema::create('student_body_duties', function (Blueprint $table) {
            $table->id();
            $table->string('short_key'); // ex. "th-2004.internationals"
            $table->string('description_keys'); // ex. "th-2004.internationals"
        });

        Schema::create('external_project_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->timestamps();
        });

        Schema::create('external_project', function (Blueprint $table) {
            $table->id();
            $table->integer('version');
            $table->string('state'); // id?
            $table->unsignedBigInteger('user_id'); // shall we? alt name: creator_id // creator_user_id

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            $table->string('target_group');
            $table->string('participants_total');
            $table->string('participants_students');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');

        });

        Schema::create('external_projects_to_student_body_duties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_body_duties_id');
            $table->unsignedBigInteger('external_project_id');

            $table->foreign('student_body_duties_id')->references('id')->on('student_body_duties');
            $table->foreign('external_project_id')->references('id')->on('external_projects');
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
