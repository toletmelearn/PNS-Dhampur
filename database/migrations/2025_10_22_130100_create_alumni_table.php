<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('alumni')) {
            Schema::create('alumni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('alumni_batches')->nullOnDelete();
                $table->string('name')->index();
                $table->string('admission_no')->nullable()->index();
                $table->unsignedInteger('pass_year')->nullable()->index();
                $table->year('graduation_year')->nullable();
                $table->string('leaving_reason')->nullable();
                $table->string('current_status')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('job_title')->nullable();
                $table->string('current_occupation')->nullable();
                $table->string('current_organization')->nullable();
                $table->string('higher_education')->nullable();
                $table->string('company')->nullable()->index();
                $table->string('industry')->nullable()->index();
                $table->string('location_city')->nullable()->index();
                $table->string('location_state')->nullable()->index();
                $table->string('location_country')->nullable()->index();
                $table->text('bio')->nullable();
                $table->text('achievements')->nullable();
                $table->unsignedInteger('achievements_count')->default(0);
                $table->decimal('contributions_total', 12, 2)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
