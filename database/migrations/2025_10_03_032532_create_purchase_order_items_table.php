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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_pending')->default(0); // Auto-calculated
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2); // quantity_ordered * unit_price
            $table->text('specifications')->nullable(); // Item-specific requirements
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'partially_received', 'fully_received', 'cancelled'])->default('pending');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            
            // Indexes for better performance
            $table->index(['purchase_order_id', 'status']);
            $table->index('inventory_item_id');
            
            // Unique constraint to prevent duplicate items in same PO
            $table->unique(['purchase_order_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
