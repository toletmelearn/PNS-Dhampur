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
        Schema::create('class_teacher_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_models')->onDelete('cascade');
            $table->unsignedBigInteger('subject_id')->nullable(); // Remove foreign key constraint for now
            
            // Permission flags
            $table->boolean('can_view_records')->default(true);
            $table->boolean('can_edit_records')->default(false);
            $table->boolean('can_add_records')->default(false);
            $table->boolean('can_delete_records')->default(false);
            $table->boolean('can_export_reports')->default(false);
            $table->boolean('can_view_attendance')->default(true);
            $table->boolean('can_mark_attendance')->default(false);
            $table->boolean('can_approve_corrections')->default(false);
            $table->boolean('can_view_audit_trail')->default(true);
            $table->boolean('can_bulk_operations')->default(false);
            
            // Validity and metadata
            $table->string('academic_year', 20);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit fields
            $table->foreignId('granted_by')->constrained('users');
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('revocation_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes with shorter names
            $table->index(['teacher_id', 'class_id', 'academic_year'], 'ctp_teacher_class_year_idx');
            $table->index(['teacher_id', 'is_active'], 'ctp_teacher_active_idx');
            $table->index(['class_id', 'subject_id'], 'ctp_class_subject_idx');
            $table->index(['academic_year', 'is_active'], 'ctp_year_active_idx');
            $table->index(['valid_from', 'valid_until'], 'ctp_validity_idx');
            
            // Unique constraint to prevent duplicate permissions
            $table->unique(['teacher_id', 'class_id', 'subject_id', 'academic_year'], 'ctp_unique_tcsy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_teacher_permissions');
    }
};