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
        if (!Schema::hasTable('paper_versions')) {
            Schema::create('paper_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('paper_id');
                $table->string('version');
                $table->text('content')->nullable();
                $table->timestamps();
            });
            
            // Add foreign key only if the related table exists
            if (Schema::hasTable('exam_papers')) {
                Schema::table('paper_versions', function (Blueprint $table) {
                    $table->foreign('paper_id')
                          ->references('id')
                          ->on('exam_papers')
                          ->onDelete('cascade');
                });
                Log::info('Foreign key constraint added to paper_versions table');
            } else {
                Log::warning('exam_papers table does not exist, foreign key constraint not added');
            }
            
            Log::info('Alternative paper_versions table created successfully');
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