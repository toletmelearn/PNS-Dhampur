<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_data_versions')) {
            Schema::table('class_data_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('class_data_versions', 'entity_type')) {
                    $table->string('entity_type')->nullable()->after('tags');
                }
                if (!Schema::hasColumn('class_data_versions', 'entity_id')) {
                    $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
                }
                // Composite index for lookups
                $table->index(['entity_type', 'entity_id'], 'entity_lookup_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_data_versions')) {
            Schema::table('class_data_versions', function (Blueprint $table) {
                // Drop index first if present
                try {
                    $table->dropIndex('entity_lookup_index');
                } catch (\Throwable $e) {
                    // ignore
                }
                if (Schema::hasColumn('class_data_versions', 'entity_id')) {
                    $table->dropColumn('entity_id');
                }
                if (Schema::hasColumn('class_data_versions', 'entity_type')) {
                    $table->dropColumn('entity_type');
                }
            });
        }
    }
};
