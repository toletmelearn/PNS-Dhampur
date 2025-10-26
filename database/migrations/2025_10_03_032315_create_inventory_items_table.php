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
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('item_code', 50)->unique(); // Unique item identifier
                $table->string('barcode')->nullable()->unique(); // Barcode for scanning
                $table->unsignedBigInteger('category_id');
                $table->string('unit_of_measurement', 20); // pieces, kg, liters, etc.
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->integer('current_stock')->default(0);
                $table->integer('minimum_stock_level')->default(0); // For low stock alerts
                $table->integer('maximum_stock_level')->nullable(); // For overstocking alerts
                $table->integer('reorder_point')->default(0); // When to reorder
                $table->integer('reorder_quantity')->default(0); // How much to reorder
                $table->string('location')->nullable(); // Storage location
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number')->nullable();
                $table->date('purchase_date')->nullable();
                $table->decimal('purchase_price', 10, 2)->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->enum('condition', ['new', 'good', 'fair', 'poor', 'damaged'])->default('new');
                $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
                $table->boolean('is_asset')->default(false); // True for capital assets
                $table->decimal('depreciation_rate', 5, 2)->nullable(); // Annual depreciation %
                $table->text('notes')->nullable();
                $table->timestamps();
                
                // Foreign key constraints
                $table->foreign('category_id')->references('id')->on('inventory_categories')->onDelete('restrict');
                
                // Indexes for better performance
                $table->index(['category_id', 'status']);
                $table->index('current_stock');
                $table->index('minimum_stock_level');
                $table->index('is_asset');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_items');
    }
};
