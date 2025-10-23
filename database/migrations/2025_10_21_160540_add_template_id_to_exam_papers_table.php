<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('exam_papers') && !Schema::hasColumn('exam_papers', 'template_id')) {
            Schema::table('exam_papers', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('instructions');
                $table->foreign('template_id')->references('id')->on('paper_templates')->onDelete('set null');
                $table->index(['template_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_papers') && Schema::hasColumn('exam_papers', 'template_id')) {
            Schema::table('exam_papers', function (Blueprint $table) {
                $table->dropForeign(['template_id']);
                $table->dropIndex(['template_id']);
                $table->dropColumn('template_id');
            });
        }
    }
};