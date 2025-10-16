<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tablesToCheck = ['teachers', 'students', 'classes', 'subjects'];

        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $tbl) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'deleted_at')) {
                        $tbl->softDeletes();
                    }
                    if (!Schema::hasColumn($tableName, 'created_at')) {
                        $tbl->timestamp('created_at')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'updated_at')) {
                        $tbl->timestamp('updated_at')->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // No rollback to avoid data loss; safe migration
    }
};