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
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code', 20)->unique(); // Category code for easy identification
            $table->unsignedBigInteger('parent_id')->nullable(); // For multi-level categorization
            $table->integer('level')->default(1); // Category level (1 = main, 2 = sub, etc.)
            $table->boolean('is_active')->default(true);
            $table->string('icon')->nullable(); // Icon class for UI
            $table->integer('sort_order')->default(0); // For ordering categories
            $table->timestamps();
            
            // Foreign key constraint for parent category
            $table->foreign('parent_id')->references('id')->on('inventory_categories')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['parent_id', 'level']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_categories');
    }
};
