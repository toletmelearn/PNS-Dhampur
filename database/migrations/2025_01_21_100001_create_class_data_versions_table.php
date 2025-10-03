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
        Schema::create('class_data_versions', function (Blueprint $table) {
            $table->id();
            
            // Relationship to audit record
            $table->unsignedBigInteger('audit_id');
            $table->foreign('audit_id')->references('id')->on('class_data_audits')->onDelete('cascade');
            
            // Version information
            $table->integer('version_number')->default(1);
            $table->longText('data_snapshot'); // Complete data state at this version
            $table->text('changes_summary')->nullable(); // Summary of changes made
            
            // Version metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('creator_name')->nullable(); // Cached creator name
            $table->enum('version_type', ['automatic', 'manual', 'scheduled', 'rollback', 'merge'])->default('automatic');
            $table->boolean('is_current_version')->default(false);
            
            // Version relationships
            $table->unsignedBigInteger('parent_version_id')->nullable(); // Previous version
            $table->json('merge_source_versions')->nullable(); // For merged versions
            
            // Data integrity and storage
            $table->string('checksum', 64)->nullable(); // SHA-256 hash of data_snapshot
            $table->unsignedBigInteger('size_bytes')->nullable(); // Size of data_snapshot
            $table->enum('compression_type', ['none', 'gzip', 'bzip2'])->default('none');
            
            // Additional metadata
            $table->json('metadata')->nullable(); // Version-specific metadata
            $table->json('tags')->nullable(); // Searchable tags
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_version_id')->references('id')->on('class_data_versions')->onDelete('set null');
            
            // Indexes for performance
            $table->index('audit_id');
            $table->index('version_number');
            $table->index('created_by');
            $table->index('version_type');
            $table->index('is_current_version');
            $table->index('parent_version_id');
            $table->index('created_at');
            $table->index('checksum');
            
            // Composite indexes
            $table->unique(['audit_id', 'version_number'], 'audit_version_unique');
            $table->index(['audit_id', 'is_current_version'], 'audit_current_version_index');
            $table->index(['created_by', 'created_at'], 'creator_timeline_index');
            $table->index(['version_type', 'created_at'], 'type_timeline_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_data_versions');
    }
};