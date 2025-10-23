<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paper_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Optional: link templates to exam papers via column if exists later
        if (Schema::hasTable('exam_papers') && !Schema::hasColumn('exam_papers', 'template_id')) {
            Schema::table('exam_papers', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('paper_type');
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
                $table->dropColumn('template_id');
            });
        }
        Schema::dropIfExists('paper_templates');
    }
};