<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('result_templates', function (Blueprint $table) {
            $table->foreign('grading_system_id')->references('id')->on('grading_systems')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('result_templates', function (Blueprint $table) {
            $table->dropForeign(['grading_system_id']);
        });
    }
};