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
        // Fix invalid section_id references by setting them to null
        DB::statement("UPDATE students SET section_id = NULL WHERE section_id IS NOT NULL AND section_id NOT IN (SELECT id FROM sections)");
        
        // Add foreign key constraint for section_id (only if not exists)
        if (!$this->foreignKeyExists('students', 'students_section_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraint if it exists
        if ($this->foreignKeyExists('students', 'students_section_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
            });
        }
    }

    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists($table, $constraintName)
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ", [$table, $constraintName]);
            
            return $result[0]->count > 0;
        }
        
        // For SQLite and other databases, assume constraint doesn't exist to allow creation
        return false;
    }
};