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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('subject')->after('name')->nullable();
            $table->time('start_time')->after('exam_date')->nullable();
            $table->time('end_time')->after('start_time')->nullable();
            $table->integer('duration')->after('end_time')->nullable()->comment('Duration in minutes');
            $table->integer('total_marks')->after('duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'subject')) {
                $table->dropColumn('subject');
            }
        });
        
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
        
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'end_time')) {
                $table->dropColumn('end_time');
            }
        });
        
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'duration')) {
                $table->dropColumn('duration');
            }
        });
        
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'total_marks')) {
                $table->dropColumn('total_marks');
            }
        });
    }
};
