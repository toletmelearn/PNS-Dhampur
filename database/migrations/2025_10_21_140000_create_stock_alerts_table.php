<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('alert_type'); // low_stock, critical_stock, out_of_stock, overstock
            $table->enum('severity', ['warning', 'critical']);
            $table->integer('current_stock')->default(0);
            $table->integer('threshold')->nullable();
            $table->integer('reorder_point')->nullable();
            $table->string('unit')->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('message')->nullable();
            $table->string('suggested_action')->nullable();
            $table->json('notified_channels')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['alert_type', 'severity']);
            $table->index(['status', 'triggered_at']);
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};