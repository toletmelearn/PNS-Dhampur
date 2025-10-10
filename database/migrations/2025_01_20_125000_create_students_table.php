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
            $table->string('admission_number')->unique(); // Changed from admission_no to admission_number
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Added gender column
            $table->string('aadhaar', 12)->nullable()->unique();
            $table->text('address')->nullable(); // Added address column
            $table->string('phone', 20)->nullable(); // Added phone column
            $table->string('email')->nullable(); // Added email column
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index(); // Added section_id column
            $table->string('roll_number')->nullable(); // Added roll_number column
            $table->date('admission_date')->nullable(); // Added admission_date column
            $table->json('documents')->nullable(); // { "birth_cert": "path", "aadhaar": "path", ... }
            $table->json('documents_verified_data')->nullable(); // extracted or admin-entered fields for comparison
            $table->enum('verification_status', ['pending','verified','mismatch'])->default('pending');
            $table->boolean('is_active')->default(true); // Changed from status enum to is_active boolean
            $table->json('meta')->nullable(); // flexible additional data
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};
