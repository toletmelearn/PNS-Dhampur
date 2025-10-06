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
        Schema::create('system_health', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->string('metric_type'); // cpu, memory, disk, database, cache, etc.
            $table->decimal('value', 8, 2);
            $table->string('unit'); // percentage, MB, GB, ms, etc.
            $table->enum('status', ['healthy', 'warning', 'critical'])->default('healthy');
            $table->text('details')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['metric_type', 'recorded_at']);
            $table->index(['status', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_health');
    }
};
