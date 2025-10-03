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
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            
            // Employee information
            $table->unsignedBigInteger('employee_id'); // References users table
            $table->string('employee_name'); // Cached for performance
            $table->string('employee_code')->nullable(); // Employee code/ID
            
            // Payroll period
            $table->integer('payroll_year'); // Financial year
            $table->integer('payroll_month'); // Month (1-12)
            $table->date('payroll_date'); // Specific payroll processing date
            $table->string('payroll_cycle')->default('monthly'); // monthly, weekly, bi-weekly
            
            // Deduction categories
            $table->enum('deduction_type', [
                'statutory', // PF, ESI, Professional Tax, TDS
                'voluntary', // LIC, Medical Insurance, Loan EMI
                'disciplinary', // Fine, Penalty
                'advance', // Salary Advance Recovery
                'loan', // Loan Recovery
                'other' // Miscellaneous
            ]);
            
            // Specific deduction details
            $table->string('deduction_code'); // PF, ESI, PT, TDS, LIC, etc.
            $table->string('deduction_name'); // Full name of deduction
            $table->text('description')->nullable(); // Description of deduction
            
            // Amount calculations
            $table->decimal('gross_salary', 10, 2); // Gross salary for calculation
            $table->decimal('basic_salary', 10, 2)->nullable(); // Basic salary component
            $table->decimal('deduction_rate', 5, 2)->nullable(); // Percentage rate (if applicable)
            $table->decimal('deduction_amount', 10, 2); // Final deduction amount
            $table->decimal('employer_contribution', 10, 2)->default(0); // Employer's share (PF, ESI)
            
            // Calculation method
            $table->enum('calculation_method', [
                'percentage', // Based on percentage of salary
                'fixed_amount', // Fixed amount
                'slab_based', // Based on salary slabs
                'manual' // Manually entered
            ])->default('percentage');
            
            // Statutory deduction specifics
            $table->string('pan_number')->nullable(); // For TDS calculations
            $table->string('pf_number')->nullable(); // PF account number
            $table->string('esi_number')->nullable(); // ESI number
            $table->decimal('tds_amount', 10, 2)->default(0); // TDS deducted
            $table->decimal('pf_employee', 10, 2)->default(0); // Employee PF contribution
            $table->decimal('pf_employer', 10, 2)->default(0); // Employer PF contribution
            $table->decimal('esi_employee', 10, 2)->default(0); // Employee ESI contribution
            $table->decimal('esi_employer', 10, 2)->default(0); // Employer ESI contribution
            $table->decimal('professional_tax', 10, 2)->default(0); // Professional tax
            
            // Loan and advance details
            $table->unsignedBigInteger('loan_id')->nullable(); // Reference to loan record
            $table->decimal('loan_balance', 10, 2)->nullable(); // Remaining loan balance
            $table->integer('installment_number')->nullable(); // Current installment number
            $table->integer('total_installments')->nullable(); // Total installments
            $table->decimal('installment_amount', 10, 2)->nullable(); // EMI amount
            
            // Voluntary deduction details
            $table->string('policy_number')->nullable(); // Insurance policy number
            $table->decimal('premium_amount', 10, 2)->nullable(); // Insurance premium
            $table->string('beneficiary')->nullable(); // Beneficiary details
            
            // Recovery and adjustment
            $table->boolean('is_recovery')->default(false); // Is this a recovery deduction?
            $table->string('recovery_reason')->nullable(); // Reason for recovery
            $table->decimal('recovery_balance', 10, 2)->nullable(); // Remaining recovery amount
            
            // Approval and authorization
            $table->enum('status', ['pending', 'approved', 'processed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            
            // Processing details
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->string('transaction_reference')->nullable(); // Bank/payment reference
            
            // Effective period
            $table->date('effective_from'); // When deduction starts
            $table->date('effective_to')->nullable(); // When deduction ends
            $table->boolean('is_recurring')->default(true); // Is this a recurring deduction?
            $table->integer('frequency_months')->default(1); // Frequency in months
            
            // Compliance and reporting
            $table->string('challan_number')->nullable(); // Government challan number
            $table->date('challan_date')->nullable(); // Challan payment date
            $table->string('return_filed')->nullable(); // Return filing reference
            $table->json('compliance_data')->nullable(); // Additional compliance data
            
            // Adjustment and correction
            $table->boolean('is_adjustment')->default(false); // Is this an adjustment entry?
            $table->string('adjustment_reason')->nullable(); // Reason for adjustment
            $table->decimal('adjustment_amount', 10, 2)->default(0); // Adjustment amount
            $table->unsignedBigInteger('original_deduction_id')->nullable(); // Reference to original
            
            // Audit and tracking
            $table->json('calculation_details')->nullable(); // Detailed calculation breakdown
            $table->text('remarks')->nullable(); // Additional remarks
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['employee_id', 'payroll_year', 'payroll_month']);
            $table->index(['deduction_type', 'deduction_code']);
            $table->index(['status', 'is_processed']);
            $table->index(['effective_from', 'effective_to']);
            $table->index(['payroll_date', 'status']);
            
            // Unique constraint to prevent duplicate deductions
            $table->unique(['employee_id', 'payroll_year', 'payroll_month', 'deduction_code'], 'unique_employee_deduction');
            
            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('original_deduction_id')->references('id')->on('payroll_deductions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_deductions');
    }
};