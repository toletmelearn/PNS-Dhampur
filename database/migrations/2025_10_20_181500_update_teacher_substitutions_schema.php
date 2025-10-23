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
        Schema::table('teacher_substitutions', function (Blueprint $table) {
            // Core date alignment
            if (!Schema::hasColumn('teacher_substitutions', 'substitution_date')) {
                $table->date('substitution_date')->nullable()->after('class_id');
                $table->index(['substitution_date', 'status'], 'ts_subdate_status_idx');
            }

            // Add columns expected by the model and controllers
            if (!Schema::hasColumn('teacher_substitutions', 'original_teacher_id')) {
                $table->foreignId('original_teacher_id')->nullable()->constrained('teachers')->onDelete('set null')->after('substitute_teacher_id');
                $table->index(['original_teacher_id', 'substitution_date'], 'ts_orig_subdate_idx');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'absence_id')) {
                $table->foreignId('absence_id')->nullable()->constrained('teacher_absences')->onDelete('set null')->after('original_teacher_id');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null')->after('class_id');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'period_number')) {
                $table->unsignedTinyInteger('period_number')->nullable()->after('subject_id');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('assigned_at');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'notification_sent')) {
                $table->boolean('notification_sent')->default(false)->after('notes');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'auto_assigned')) {
                $table->boolean('auto_assigned')->default(false)->after('notification_sent');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'preparation_materials')) {
                $table->text('preparation_materials')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'feedback')) {
                $table->text('feedback')->nullable()->after('preparation_materials');
            }

            if (!Schema::hasColumn('teacher_substitutions', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('feedback');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_substitutions', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_substitutions', 'substitution_date')) {
                $table->dropIndex('ts_subdate_status_idx');
                $table->dropColumn('substitution_date');
            }
            if (Schema::hasColumn('teacher_substitutions', 'original_teacher_id')) {
                $table->dropIndex('ts_orig_subdate_idx');
                $table->dropConstrainedForeignId('original_teacher_id');
            }
            if (Schema::hasColumn('teacher_substitutions', 'absence_id')) {
                $table->dropConstrainedForeignId('absence_id');
            }
            if (Schema::hasColumn('teacher_substitutions', 'subject_id')) {
                $table->dropConstrainedForeignId('subject_id');
            }
            if (Schema::hasColumn('teacher_substitutions', 'period_number')) {
                $table->dropColumn('period_number');
            }
            if (Schema::hasColumn('teacher_substitutions', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
            if (Schema::hasColumn('teacher_substitutions', 'notification_sent')) {
                $table->dropColumn('notification_sent');
            }
            if (Schema::hasColumn('teacher_substitutions', 'auto_assigned')) {
                $table->dropColumn('auto_assigned');
            }
            if (Schema::hasColumn('teacher_substitutions', 'feedback')) {
                $table->dropColumn('feedback');
            }
            if (Schema::hasColumn('teacher_substitutions', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('teacher_substitutions', 'preparation_materials')) {
                $table->dropColumn('preparation_materials');
            }
        });
    }
};