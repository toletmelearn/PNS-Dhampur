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
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('joining_date');
            }
            if (!Schema::hasColumn('teachers', 'can_substitute')) {
                $table->boolean('can_substitute')->default(true)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'can_substitute')) {
                $table->dropColumn('can_substitute');
            }
            if (Schema::hasColumn('teachers', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};