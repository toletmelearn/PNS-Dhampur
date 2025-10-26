<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertEnumColumnsToString extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert all ENUM columns to string to avoid Doctrine DBAL issues
        $this->convertEnumsToString();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot easily convert back to ENUM, so this is one-way
    }

    protected function convertEnumsToString()
    {
        // List of tables and their ENUM columns to convert
        $enumColumns = [
            // Example: ['table' => 'users', 'columns' => ['status' => 'active']]
        ];

        foreach ($enumColumns as $tableConfig) {
            $tableName = $tableConfig['table'];
            
            if (Schema::hasTable($tableName)) {
                foreach ($tableConfig['columns'] as $column => $defaultValue) {
                    if (Schema::hasColumn($tableName, $column)) {
                        // Convert ENUM to string
                        Schema::table($tableName, function (Blueprint $table) use ($column, $defaultValue) {
                            $table->string($column, 50)->default($defaultValue)->change();
                        });
                    }
                }
            }
        }
     }
}
