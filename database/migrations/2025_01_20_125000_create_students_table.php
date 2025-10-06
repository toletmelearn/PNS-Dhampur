<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique(); // link to user if needed
            $table->string('admission_no')->unique(); // Fixed: Made required (not nullable)
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('aadhaar')->nullable()->unique();
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->json('documents')->nullable(); // { "birth_cert": "path", "aadhaar": "path", ... }
            $table->json('documents_verified_data')->nullable(); // extracted or admin-entered fields for comparison
            $table->enum('verification_status', ['pending','verified','mismatch'])->default('pending');
            $table->enum('status', ['active','left','alumni'])->default('active');
            $table->json('meta')->nullable(); // flexible additional data (address, phone...)
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};
