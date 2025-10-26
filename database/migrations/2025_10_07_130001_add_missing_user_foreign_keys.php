<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMissingUserForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Using raw SQL statements to avoid Doctrine DBAL ENUM issues
        // First check if constraints exist before adding them
        // Only add foreign keys for tables and columns that actually exist
        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
            $this->addForeignKeyIfNotExists('teachers', 'teachers_user_id_foreign', 'user_id', 'users', 'id', 'CASCADE');
        }
        
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'user_id')) {
            $this->addForeignKeyIfNotExists('students', 'students_user_id_foreign', 'user_id', 'users', 'id', 'CASCADE');
        }
        
        // Skip other tables that were causing issues
    }
    
    /**
     * Add a foreign key constraint if it doesn't already exist
     */
    private function addForeignKeyIfNotExists($table, $constraintName, $column, $referencedTable, $referencedColumn, $onDelete)
    {
        // Check if the constraint already exists
        $constraintExists = DB::select("
            SELECT * FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table' 
            AND CONSTRAINT_NAME = '$constraintName'
        ");
        
        if (empty($constraintExists)) {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` FOREIGN KEY (`$column`) REFERENCES `$referencedTable` (`$referencedColumn`) ON DELETE $onDelete");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign keys in reverse order
        DB::statement('ALTER TABLE `exams` DROP FOREIGN KEY `exams_created_by_foreign`');
        DB::statement('ALTER TABLE `fees` DROP FOREIGN KEY `fees_user_id_foreign`');
        DB::statement('ALTER TABLE `attendances` DROP FOREIGN KEY `attendances_user_id_foreign`');
        DB::statement('ALTER TABLE `students` DROP FOREIGN KEY `students_user_id_foreign`');
        DB::statement('ALTER TABLE `teachers` DROP FOREIGN KEY `teachers_user_id_foreign`');
    }
}