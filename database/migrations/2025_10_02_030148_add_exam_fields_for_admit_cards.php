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
            $table->dropColumn(['subject', 'start_time', 'end_time', 'duration', 'total_marks']);
        });
    }
};
