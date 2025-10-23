<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->dateTime('start_date')->index();
            $table->dateTime('end_date')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('registration_url')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('alumni_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('alumni_events')->cascadeOnDelete();
            $table->foreignId('alumni_id')->nullable()->constrained('alumni')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['event_id','alumni_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_event_registrations');
        Schema::dropIfExists('alumni_events');
    }
};
