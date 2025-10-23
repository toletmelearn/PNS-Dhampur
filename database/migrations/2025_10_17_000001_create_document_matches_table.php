<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verification_id');
            $table->string('field');
            $table->text('expected_value')->nullable();
            $table->text('document_value')->nullable();
            $table->decimal('similarity_score', 5, 2)->nullable(); // 0-1 scaled to percentage if needed
            $table->decimal('confidence', 5, 2)->nullable(); // 0-1
            $table->string('mismatch_type')->nullable();
            $table->string('severity')->nullable(); // low, medium, high
            $table->boolean('auto_resolvable')->default(false);
            $table->json('suggestions')->nullable();
            $table->string('source_document_type')->nullable();
            $table->timestamps();

            $table->foreign('verification_id')
                  ->references('id')->on('student_verifications')
                  ->onDelete('cascade');

            $table->index(['verification_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_matches');
    }
};