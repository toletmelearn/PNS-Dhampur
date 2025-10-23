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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('is_disposed')->default(false)->index();
            $table->timestamp('disposed_at')->nullable()->index();
            $table->unsignedBigInteger('disposed_by')->nullable();
            $table->string('disposal_reason')->nullable();
            $table->string('disposal_method')->nullable(); // sold, scrapped, donated, transferred
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->text('disposal_notes')->nullable();

            $table->foreign('disposed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['disposed_by']);
            $table->dropColumn([
                'is_disposed',
                'disposed_at',
                'disposed_by',
                'disposal_reason',
                'disposal_method',
                'disposal_value',
                'disposal_notes',
            ]);
        });
    }
};