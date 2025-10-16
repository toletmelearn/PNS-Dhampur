<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_role_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_role_assignments', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_role_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('user_role_assignments', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};