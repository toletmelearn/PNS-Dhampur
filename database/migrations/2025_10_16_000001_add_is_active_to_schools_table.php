<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('schools') && !Schema::hasColumn('schools', 'is_active')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'is_active')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};