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
        Schema::create('asset_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('allocated_to_type'); // 'classroom', 'teacher', 'department', 'student'
            $table->unsignedBigInteger('allocated_to_id'); // ID of the entity (class_id, teacher_id, etc.)
            $table->string('allocated_to_name'); // Name for easy reference
            $table->unsignedBigInteger('allocated_by'); // User who made the allocation
            $table->date('allocation_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('status', ['allocated', 'in_use', 'returned', 'lost', 'damaged'])->default('allocated');
            $table->text('allocation_purpose')->nullable();
            $table->text('condition_at_allocation')->nullable();
            $table->text('condition_at_return')->nullable();
            $table->text('usage_notes')->nullable();
            $table->integer('usage_hours')->default(0); // For equipment with usage tracking
            $table->decimal('damage_cost', 10, 2)->default(0); // Cost of any damage
            $table->text('return_notes')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable(); // User who processed return
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('allocated_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('returned_by')->references('id')->on('users')->onDelete('restrict');
            
            // Indexes for better performance
            $table->index(['allocated_to_type', 'allocated_to_id']);
            $table->index(['status', 'allocation_date']);
            $table->index('inventory_item_id');
            $table->index('allocated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_allocations');
    }
};
