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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('maintenance_type'); // 'preventive', 'corrective', 'emergency'
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->time('estimated_duration')->nullable(); // How long maintenance should take
            $table->time('actual_duration')->nullable(); // How long it actually took
            $table->enum('frequency', ['one_time', 'daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual'])->default('one_time');
            $table->integer('frequency_interval')->default(1); // Every X frequency units
            $table->date('next_due_date')->nullable(); // Auto-calculated for recurring maintenance
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'overdue'])->default('scheduled');
            $table->unsignedBigInteger('assigned_to')->nullable(); // User responsible for maintenance
            $table->unsignedBigInteger('completed_by')->nullable(); // User who completed maintenance
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->string('vendor_name')->nullable(); // External maintenance vendor
            $table->text('work_performed')->nullable(); // What was actually done
            $table->text('parts_replaced')->nullable(); // Parts that were replaced
            $table->text('notes')->nullable();
            $table->boolean('requires_downtime')->default(false); // If asset will be unavailable
            $table->timestamp('reminder_sent_at')->nullable(); // When reminder was last sent
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['status', 'scheduled_date']);
            $table->index(['inventory_item_id', 'status']);
            $table->index('next_due_date');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
