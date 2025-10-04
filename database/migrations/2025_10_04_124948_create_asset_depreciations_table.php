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
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'double_declining', 'sum_of_years_digits'])->default('straight_line');
            $table->integer('useful_life_years');
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2);
            $table->date('purchase_date');
            $table->date('depreciation_start_date');
            $table->decimal('annual_depreciation_rate', 5, 2)->nullable();
            $table->decimal('current_book_value', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->date('last_calculation_date')->nullable();
            $table->date('next_calculation_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->json('calculation_history')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['inventory_item_id', 'is_active']);
            $table->index(['depreciation_method', 'is_active']);
            $table->index(['next_calculation_date', 'is_active']);
            $table->index('last_calculation_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
