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
        // If the roles table already exists (created by an earlier migration),
        // make this migration idempotent by only adding missing columns.
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                // Add sort_order if it's missing
                if (!Schema::hasColumn('roles', 'sort_order')) {
                    $table->integer('sort_order')->default(0);
                }
                // Ensure permissions column exists as json (older migration already adds it)
                // No-op if it already exists
                if (!Schema::hasColumn('roles', 'permissions')) {
                    $table->json('permissions')->nullable();
                }
                // Ensure is_active column exists (older migration already adds it)
                if (!Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
            
            // Skip adding indexes here to avoid duplicate index issues when multiple migrations define them.
            return;
        }

        // Otherwise, create the roles table fresh
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
        Schema::dropIfExists('roles');
    }
};
