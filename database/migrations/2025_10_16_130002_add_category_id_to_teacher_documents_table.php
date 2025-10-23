<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_documents') && !Schema::hasColumn('teacher_documents', 'category_id')) {
            Schema::table('teacher_documents', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('document_type')
                    ->constrained('document_categories')->onDelete('set null');
                $table->index('category_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_documents') && Schema::hasColumn('teacher_documents', 'category_id')) {
            Schema::table('teacher_documents', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
                $table->dropIndex(['category_id']);
                $table->dropColumn('category_id');
            });
        }
    }
};