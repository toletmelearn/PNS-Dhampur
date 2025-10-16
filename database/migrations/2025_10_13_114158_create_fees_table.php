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
        // If the fees table already exists (created by an earlier migration),
        // make this migration idempotent by skipping re-creation to avoid errors.
        if (Schema::hasTable('fees')) {
            return;
        }

        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
            $table->string('academic_year');
            
            // Fee Structure
            $table->string('fee_type'); // Tuition, Transport, Library, Lab, etc.
            $table->string('fee_category')->default('regular'); // regular, scholarship, concession
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            
            // Payment Schedule
            $table->enum('frequency', ['monthly', 'quarterly', 'half_yearly', 'yearly', 'one_time'])->default('monthly');
            $table->date('due_date');
            $table->date('last_date')->nullable(); // Last date without fine
            $table->integer('installment_number')->default(1);
            $table->integer('total_installments')->default(1);
            
            // Payment Status
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived', 'cancelled'])->default('pending');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->timestamp('payment_deadline')->nullable();
            
            // Discount and Concession
            $table->string('discount_type')->nullable(); // Scholarship, Sibling, Merit, etc.
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('discount_reason')->nullable();
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('discount_approved_at')->nullable();
            
            // Fine and Penalty
            $table->decimal('fine_percentage', 5, 2)->default(0);
            $table->decimal('fine_per_day', 8, 2)->default(0);
            $table->integer('grace_period_days')->default(0);
            $table->date('fine_applicable_from')->nullable();
            $table->boolean('fine_waived')->default(false);
            $table->text('fine_waiver_reason')->nullable();
            
            // Payment Method and Receipt
            $table->string('payment_method')->nullable(); // Cash, Cheque, Online, Card, etc.
            $table->string('transaction_id')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            
            // Collection Information
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('collected_at')->nullable();
            $table->string('collection_center')->nullable();
            $table->boolean('receipt_generated')->default(false);
            $table->boolean('receipt_sent')->default(false);
            
            // Parent Notification
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->boolean('overdue_notice_sent')->default(false);
            $table->timestamp('overdue_notice_sent_at')->nullable();
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_refundable')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('refund_reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['student_id', 'academic_year']);
            $table->index(['class_id', 'section_id', 'academic_year']);
            $table->index(['fee_type', 'fee_category']);
            $table->index(['status', 'due_date']);
            $table->index(['payment_deadline', 'status']);
            $table->index(['collected_by', 'collected_at']);
            $table->index(['receipt_number', 'transaction_id']);
            $table->index(['reminder_sent', 'overdue_notice_sent']);
            $table->index(['is_active', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fees');
    }
};
