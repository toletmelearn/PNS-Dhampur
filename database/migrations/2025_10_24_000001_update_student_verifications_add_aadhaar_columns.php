<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('student_verifications', 'verification_type')) {
                $table->string('verification_type')->nullable()->index();
            }
            if (!Schema::hasColumn('student_verifications', 'status')) {
                $table->string('status')->nullable()->index();
            }
            if (!Schema::hasColumn('student_verifications', 'match_score')) {
                $table->decimal('match_score', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('student_verifications', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('student_verifications', 'verification_type')) {
                $table->dropColumn('verification_type');
            }
            if (Schema::hasColumn('student_verifications', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('student_verifications', 'match_score')) {
                $table->dropColumn('match_score');
            }
            if (Schema::hasColumn('student_verifications', 'verified_by')) {
                $table->dropColumn('verified_by');
            }
        });
    }
};
