<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMissingUserForeignKeys extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to avoid Doctrine DBAL enum issues
        $this->addForeignKeysWithRawSQL();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to drop foreign keys
        $this->dropForeignKeysWithRawSQL();
    }

    protected function addForeignKeysWithRawSQL()
    {
        // List of foreign keys to add (avoiding tables with ENUM columns)
        $foreignKeys = [
            // Only add foreign keys to tables without ENUM columns
            // Example: ['table' => 'students', 'column' => 'user_id', 'references' => 'users', 'ref_column' => 'id']
        ];

        foreach ($foreignKeys as $fk) {
            if ($this->tableExists($fk['table']) && $this->tableExists($fk['references'])) {
                $constraintName = "fk_{$fk['table']}_{$fk['column']}";
                
                if (!$this->foreignKeyExists($fk['table'], $constraintName)) {
                    DB::statement("
                        ALTER TABLE {$fk['table']}
                        ADD CONSTRAINT {$constraintName}
                        FOREIGN KEY ({$fk['column']})
                        REFERENCES {$fk['references']}({$fk['ref_column']})
                        ON DELETE CASCADE
                    ");
                }
            }
        }
    }

    protected function dropForeignKeysWithRawSQL()
    {
        // Drop the same foreign keys
        $foreignKeys = [
            // Same list as above
        ];

        foreach ($foreignKeys as $fk) {
            if ($this->tableExists($fk['table'])) {
                $constraintName = "fk_{$fk['table']}_{$fk['column']}";
                
                if ($this->foreignKeyExists($fk['table'], $constraintName)) {
                    DB::statement("ALTER TABLE {$fk['table']} DROP FOREIGN KEY {$constraintName}");
                }
            }
        }
    }

    protected function tableExists($tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    protected function foreignKeyExists($tableName, $constraintName): bool
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ?
                AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [DB::getDatabaseName(), $tableName, $constraintName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}