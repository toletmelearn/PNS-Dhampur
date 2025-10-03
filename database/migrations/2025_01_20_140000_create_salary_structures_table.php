<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Primary Teacher Grade A", "Principal", "Administrative Staff"
            $table->string('code')->unique(); // e.g., "PTA", "PRIN", "ADMIN"
            $table->text('description')->nullable();
            
            // Basic salary information
            $table->decimal('basic_salary', 10, 2); // Base salary amount
            $table->decimal('minimum_salary', 10, 2)->nullable(); // Minimum salary for this grade
            $table->decimal('maximum_salary', 10, 2)->nullable(); // Maximum salary for this grade
            $table->integer('increment_percentage')->default(5); // Annual increment percentage
            
            // Allowances (stored as JSON for flexibility)
            $table->json('allowances')->nullable(); // HRA, DA, TA, Medical, etc.
            $table->json('allowance_rules')->nullable(); // Rules for calculating allowances
            
            // Deductions (stored as JSON for flexibility)
            $table->json('deductions')->nullable(); // PF, ESI, Professional Tax, etc.
            $table->json('deduction_rules')->nullable(); // Rules for calculating deductions
            
            // Benefits and perks
            $table->json('benefits')->nullable(); // Leave encashment, bonus, etc.
            $table->json('benefit_rules')->nullable(); // Rules for calculating benefits
            
            // Grade and level information
            $table->string('grade_level')->nullable(); // A, B, C, etc.
            $table->integer('experience_required')->default(0); // Minimum experience in years
            $table->string('qualification_required')->nullable(); // Required qualifications
            
            // Effective dates
            $table->date('effective_from'); // When this structure becomes effective
            $table->date('effective_to')->nullable(); // When this structure expires
            
            // Status and approval
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Audit fields
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'effective_from', 'effective_to']);
            $table->index(['grade_level', 'status']);
            $table->index('code');
            
            // Foreign key constraints
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_structures');
    }
};