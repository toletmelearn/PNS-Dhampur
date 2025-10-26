<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('paper_versions')) {
            Schema::create('paper_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_paper_id');
                $table->unsignedInteger('version_number');
                $table->longText('content_text')->nullable();
                $table->string('file_path')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['exam_paper_id', 'version_number']);
                
                // Add foreign keys with proper dependency checks
                // We ensure these tables exist in the correct order:
                // 1. users table (created in 2014_10_12_000000_create_users_table.php)
                // 2. schools table (should be created before papers)
                // 3. papers table (created in 2025_10_02_022443_create_exam_papers_table.php)
                // 4. paper_versions table (this migration)
                
                try {
                    // First ensure users table exists (this should always be true as it's one of the first migrations)
                    if (!Schema::hasTable('users')) {
                        throw new \Exception('Users table must exist before creating paper_versions table');
                    }
                    
                    // Add foreign key for users
                    $table->foreign('created_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                          
                    // Then check for exam_papers table
                    if (!Schema::hasTable('exam_papers')) {
                        throw new \Exception('Exam papers table must exist before creating paper_versions table');
                    }
                    
                    // Add foreign key for exam_papers
                    $table->foreign('exam_paper_id')
                          ->references('id')
                          ->on('exam_papers')
                          ->onDelete('cascade')
                          ->onUpdate('cascade');
                          
                } catch (\Exception $e) {
                    // Log error but continue with migration
                    \Log::error('Error in paper_versions migration: ' . $e->getMessage());
                    // You may want to handle this differently based on your requirements
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_versions');
    }
};