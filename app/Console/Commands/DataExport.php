<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;

class DataExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:export {--format=json : Export format (json, csv, sql)} {--tables= : Comma-separated list of tables to export} {--exclude= : Comma-separated list of tables to exclude}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database data in various formats for migration or backup purposes';

    /**
     * Tables to exclude by default (sensitive data)
     */
    private $defaultExcludeTables = [
        'password_resets',
        'failed_jobs',
        'personal_access_tokens',
        'sessions'
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting data export...');
        
        try {
            $format = $this->option('format');
            $tablesOption = $this->option('tables');
            $excludeOption = $this->option('exclude');
            
            // Validate format
            if (!in_array($format, ['json', 'csv', 'sql'])) {
                $this->error('Invalid format. Supported formats: json, csv, sql');
                return Command::FAILURE;
            }
            
            // Get tables to export
            $tables = $this->getTablesToExport($tablesOption, $excludeOption);
            
            if (empty($tables)) {
                $this->error('No tables to export');
                return Command::FAILURE;
            }
            
            // Create export directory
            $exportDir = storage_path('app/exports');
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            
            // Generate export filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "data_export_{$format}_{$timestamp}";
            
            $this->info("📊 Exporting " . count($tables) . " tables in {$format} format...");
            
            switch ($format) {
                case 'json':
                    $result = $this->exportToJson($tables, $exportDir, $filename);
                    break;
                case 'csv':
                    $result = $this->exportToCsv($tables, $exportDir, $filename);
                    break;
                case 'sql':
                    $result = $this->exportToSql($tables, $exportDir, $filename);
                    break;
            }
            
            $this->info("✅ Data export completed successfully!");
            $this->info("📁 File(s): {$result['files']}");
            $this->info("📊 Records: {$result['records']}");
            $this->info("📍 Location: {$result['path']}");
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("❌ Data export failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Get tables to export
     */
    private function getTablesToExport($tablesOption, $excludeOption)
    {
        $excludeTables = array_merge(
            $this->defaultExcludeTables,
            $excludeOption ? explode(',', $excludeOption) : []
        );
        
        if ($tablesOption) {
            // Export specific tables
            $tables = explode(',', $tablesOption);
            return array_diff($tables, $excludeTables);
        } else {
            // Export all tables except excluded ones
            $allTables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
            return array_diff($allTables, $excludeTables);
        }
    }
    
    /**
     * Export data to JSON format
     */
    private function exportToJson($tables, $exportDir, $filename)
    {
        $filepath = $exportDir . '/' . $filename . '.json';
        $exportData = [];
        $totalRecords = 0;
        
        foreach ($tables as $table) {
            $this->info("📄 Exporting table: {$table}");
            $data = DB::table($table)->get()->toArray();
            $exportData[$table] = $data;
            $totalRecords += count($data);
            $this->info("   ✓ {$table}: " . count($data) . " records");
        }
        
        // Add metadata
        $exportData['_metadata'] = [
            'export_date' => Carbon::now()->toISOString(),
            'format' => 'json',
            'tables' => count($tables),
            'total_records' => $totalRecords,
            'exported_tables' => $tables
        ];
        
        file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));
        
        return [
            'files' => $filename . '.json',
            'records' => $totalRecords,
            'path' => $filepath
        ];
    }
    
    /**
     * Export data to CSV format
     */
    private function exportToCsv($tables, $exportDir, $filename)
    {
        $csvDir = $exportDir . '/' . $filename . '_csv';
        if (!file_exists($csvDir)) {
            mkdir($csvDir, 0755, true);
        }
        
        $totalRecords = 0;
        $files = [];
        
        foreach ($tables as $table) {
            $this->info("📄 Exporting table: {$table}");
            $csvFile = $csvDir . '/' . $table . '.csv';
            $handle = fopen($csvFile, 'w');
            
            $data = DB::table($table)->get();
            
            if ($data->count() > 0) {
                // Write headers
                $headers = array_keys((array) $data->first());
                fputcsv($handle, $headers);
                
                // Write data
                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
                
                $totalRecords += $data->count();
                $files[] = $table . '.csv';
                $this->info("   ✓ {$table}: " . $data->count() . " records");
            }
            
            fclose($handle);
        }
        
        // Create metadata file
        $metadataFile = $csvDir . '/metadata.json';
        $metadata = [
            'export_date' => Carbon::now()->toISOString(),
            'format' => 'csv',
            'tables' => count($tables),
            'total_records' => $totalRecords,
            'exported_tables' => $tables,
            'files' => $files
        ];
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        
        return [
            'files' => count($files) . ' CSV files',
            'records' => $totalRecords,
            'path' => $csvDir
        ];
    }
    
    /**
     * Export data to SQL format
     */
    private function exportToSql($tables, $exportDir, $filename)
    {
        $filepath = $exportDir . '/' . $filename . '.sql';
        $handle = fopen($filepath, 'w');
        $totalRecords = 0;
        
        // Write header
        fwrite($handle, "-- Data Export\n");
        fwrite($handle, "-- Generated: " . Carbon::now()->toISOString() . "\n");
        fwrite($handle, "-- Tables: " . implode(', ', $tables) . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
        
        foreach ($tables as $table) {
            $this->info("📄 Exporting table: {$table}");
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$table}`")[0];
            fwrite($handle, "-- Table: {$table}\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createTable->{'Create Table'} . ";\n\n");
            
            // Get table data
            $data = DB::table($table)->get();
            
            if ($data->count() > 0) {
                fwrite($handle, "-- Data for table: {$table}\n");
                
                foreach ($data as $row) {
                    $values = [];
                    foreach ((array) $row as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    
                    $columns = implode('`, `', array_keys((array) $row));
                    $valuesStr = implode(', ', $values);
                    fwrite($handle, "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$valuesStr});\n");
                }
                
                $totalRecords += $data->count();
                $this->info("   ✓ {$table}: " . $data->count() . " records");
            }
            
            fwrite($handle, "\n");
        }
        
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);
        
        return [
            'files' => $filename . '.sql',
            'records' => $totalRecords,
            'path' => $filepath
        ];
    }
}
