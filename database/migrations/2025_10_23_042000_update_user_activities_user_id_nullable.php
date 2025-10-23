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
        if (Schema::hasTable('user_activities')) {
            // Drop existing FK to modify column
            Schema::table('user_activities', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Make user_id nullable and re-add FK with SET NULL
            Schema::table('user_activities', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_activities')) {
            // Drop FK to revert column
            Schema::table('user_activities', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Revert to NOT NULL and cascade delete
            Schema::table('user_activities', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};