<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('substitution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substitution_id')->constrained('teacher_substitutions')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->string('action'); // assigned, confirmed, declined, completed, cancelled
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data like reason, rating, etc.
            $table->timestamp('action_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('substitution_logs');
    }
};
