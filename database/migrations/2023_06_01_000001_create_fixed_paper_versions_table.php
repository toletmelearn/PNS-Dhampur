<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (!Schema::hasTable('paper_versions')) {
                Schema::create('paper_versions', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('paper_id')->constrained('exam_papers')->onDelete('cascade');
                    $table->string('version');
                    $table->text('content')->nullable();
                    $table->timestamps();
                });
                Log::info('paper_versions table created successfully');
            }
        } catch (\Exception $e) {
            // Alternative approach if foreign key constraint fails
            if (!Schema::hasTable('paper_versions')) {
                Schema::create('paper_versions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('paper_id');
                    $table->string('version');
                    $table->text('content')->nullable();
                    $table->timestamps();
                    
                    // Skip foreign key constraint if it causes issues
                    if (Schema::hasTable('exam_papers')) {
                        $table->foreign('paper_id')->references('id')->on('exam_papers')->onDelete('cascade');
                    }
                });
                Log::info('paper_versions table created with fallback approach');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_versions');
    }
};