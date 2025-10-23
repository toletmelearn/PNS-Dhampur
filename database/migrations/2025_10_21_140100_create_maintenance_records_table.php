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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('maintenance_schedule_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // users.id
            $table->dateTime('maintenance_date');
            $table->string('maintenance_type');
            $table->text('issue_description')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->decimal('downtime_hours', 8, 2)->nullable();
            $table->string('status')->default('completed');
            $table->boolean('warranty_claim')->default(false);
            $table->text('warranty_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('maintenance_schedule_id')->references('id')->on('maintenance_schedules')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['inventory_item_id', 'maintenance_date']);
            $table->index(['status', 'maintenance_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};