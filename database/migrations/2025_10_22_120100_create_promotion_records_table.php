<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_class')->nullable()->constrained('class_models')->nullOnDelete();
            $table->foreignId('to_class')->nullable()->constrained('class_models')->nullOnDelete();
            $table->string('academic_year')->index();
            $table->date('promotion_date')->nullable()->index();
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('recorded')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_records');
    }
};
