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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Component name (e.g., Basic Salary, HRA, DA, etc.)
            $table->string('code')->unique(); // Unique code for the component
            $table->enum('type', ['allowance', 'deduction', 'benefit']); // Component type
            $table->enum('calculation_method', ['fixed', 'percentage', 'formula']); // How to calculate
            $table->decimal('fixed_amount', 10, 2)->nullable(); // Fixed amount if applicable
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage if applicable
            $table->text('formula')->nullable(); // Formula for complex calculations
            $table->string('based_on')->nullable(); // What the calculation is based on (basic_salary, gross_salary, etc.)
            $table->boolean('is_taxable')->default(true); // Whether component is taxable
            $table->boolean('is_statutory')->default(false); // Whether it's a statutory component (PF, ESI, etc.)
            $table->boolean('is_active')->default(true); // Whether component is active
            $table->integer('display_order')->default(0); // Order for display in payslips
            $table->text('description')->nullable(); // Description of the component
            $table->json('rules')->nullable(); // Additional rules and conditions
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum amount if applicable
            $table->decimal('maximum_amount', 10, 2)->nullable(); // Maximum amount if applicable
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'is_active']);
            $table->index(['code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_components');
    }
};
