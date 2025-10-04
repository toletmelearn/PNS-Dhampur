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
        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_depreciation_id')->constrained('asset_depreciations')->onDelete('cascade');
            $table->date('calculation_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->string('calculation_method', 50);
            $table->boolean('is_manual_entry')->default(false);
            $table->string('adjustment_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('calculation_details')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['asset_depreciation_id', 'calculation_date'], 'dep_entries_asset_calc_date_idx');
            $table->index(['calculation_date', 'is_manual_entry'], 'dep_entries_calc_date_manual_idx');
            $table->index('is_manual_entry', 'dep_entries_manual_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
