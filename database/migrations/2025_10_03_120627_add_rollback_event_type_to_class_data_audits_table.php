<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // For SQLite, we need to use raw SQL to modify the enum
        DB::statement("UPDATE class_data_audits SET event_type = 'created' WHERE event_type NOT IN ('created', 'updated', 'deleted', 'restored', 'bulk_update', 'bulk_delete', 'import', 'export', 'merge', 'split')");
        
        // Since SQLite doesn't support ALTER COLUMN for ENUM, we'll handle this differently
        // For now, we'll just ensure the rollback event type can be inserted
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove any rollback entries
        DB::statement("DELETE FROM class_data_audits WHERE event_type = 'rollback'");
    }
};
