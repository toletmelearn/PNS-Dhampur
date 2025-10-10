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
        // First, update any null aadhaar values with a temporary unique value
        // Use SQLite-compatible string concatenation
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE students SET aadhaar = 'TEMP_' || id || '_' || strftime('%s', 'now') WHERE aadhaar IS NULL OR aadhaar = ''");
        } else {
            DB::statement("UPDATE students SET aadhaar = CONCAT('TEMP_', id, '_', UNIX_TIMESTAMP()) WHERE aadhaar IS NULL OR aadhaar = ''");
        }
        
        // Fix invalid user_id references by setting them to null
        DB::statement("UPDATE students SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)");
        
        Schema::table('students', function (Blueprint $table) {
            // Ensure admission_number is not nullable and unique (should already be set)
            if (!$this->hasUniqueConstraint('students', 'admission_number')) {
                $table->string('admission_number')->unique()->change();
            }
            
            // Make aadhaar not nullable and ensure it's unique
            $table->string('aadhaar')->nullable(false)->change();
            if (!$this->hasUniqueConstraint('students', 'aadhaar')) {
                $table->unique('aadhaar');
            }
            
            // Ensure class_id foreign key exists with proper cascade
            if (!$this->hasForeignKey('students', 'class_id')) {
                $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            }
            
            // Ensure user_id foreign key exists if not already present
            if (!$this->hasForeignKey('students', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
        
        // Add indexes for better performance
        Schema::table('students', function (Blueprint $table) {
            if (!$this->hasIndex('students', 'verification_status')) {
                $table->index('verification_status');
            }
            if (!$this->hasIndex('students', 'is_active')) {
                $table->index('is_active');
            }
            if (!$this->hasIndex('students', ['class_id', 'is_active'])) {
                $table->index(['class_id', 'is_active']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            // Make aadhaar nullable again
            $table->string('aadhaar')->nullable()->change();
            
            // Drop indexes if they exist
            if ($this->hasIndex('students', 'verification_status')) {
                $table->dropIndex(['verification_status']);
            }
            if ($this->hasIndex('students', 'is_active')) {
                $table->dropIndex(['is_active']);
            }
            if ($this->hasIndex('students', ['class_id', 'is_active'])) {
                $table->dropIndex(['class_id', 'is_active']);
            }
        });
    }
    
    /**
     * Check if a unique constraint exists on a column
     */
    private function hasUniqueConstraint($table, $column)
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME LIKE ?
            ", [$table, "%{$column}%"]);
            
            return count($constraints) > 0;
        }
        
        // For SQLite and other databases, assume constraint exists to avoid errors
        return true;
    }
    
    /**
     * Check if a foreign key constraint exists
     */
    private function hasForeignKey($table, $column)
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $column]);
            
            return count($constraints) > 0;
        }
        
        // For SQLite and other databases, assume constraint exists to avoid errors
        return true;
    }
    
    /**
     * Check if an index exists
     */
    private function hasIndex($table, $columns)
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $columnList = is_array($columns) ? implode(',', $columns) : $columns;
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            
            foreach ($indexes as $index) {
                if (strpos($index->Key_name, $columnList) !== false) {
                    return true;
                }
            }
            return false;
        } elseif ($driver === 'sqlite') {
            // For SQLite, check using PRAGMA index_list
            try {
                $indexes = DB::select("PRAGMA index_list({$table})");
                $columnList = is_array($columns) ? implode('_', $columns) : $columns;
                $expectedIndexName = "{$table}_{$columnList}_index";
                
                foreach ($indexes as $index) {
                    if ($index->name === $expectedIndexName) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }
        
        // For other databases, assume index exists to avoid errors
        return true;
    }
};