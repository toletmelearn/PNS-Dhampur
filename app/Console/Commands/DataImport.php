<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;

class DataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:import {file : Path to the import file} {--format=auto : Import format (auto, json, csv, sql)} {--truncate : Truncate tables before import} {--ignore-errors : Continue on errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import database data from various formats for migration or restoration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting data import...');
        
        try {
            $filePath = $this->argument('file');
            $format = $this->option('format');
            $truncate = $this->option('truncate');
            $ignoreErrors = $this->option('ignore-errors');
            
            // Check if file exists
            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return Command::FAILURE;
            }
            
            // Auto-detect format if needed
            if ($format === 'auto') {
                $format = $this->detectFormat($filePath);
                $this->info("Auto-detected format: {$format}");
            }
            
            // Validate format
            if (!in_array($format, ['json', 'csv', 'sql'])) {
                $this->error('Invalid format. Supported formats: json, csv, sql');
                return Command::FAILURE;
            }
            
            // Confirm destructive operations
            if ($truncate && !$this->confirm('This will truncate tables before import. Are you sure?')) {
                $this->info('Import cancelled.');
                return Command::SUCCESS;
            }
            
            $this->info("📥 Importing data from {$filePath} in {$format} format...");
            
            switch ($format) {
                case 'json':
                    $result = $this->importFromJson($filePath, $truncate, $ignoreErrors);
                    break;
                case 'csv':
                    $result = $this->importFromCsv($filePath, $truncate, $ignoreErrors);
                    break;
                case 'sql':
                    $result = $this->importFromSql($filePath, $ignoreErrors);
                    break;
            }
            
            $this->info("✅ Data import completed successfully!");
            $this->info("📊 Tables: {$result['tables']}");
            $this->info("📈 Records: {$result['records']}");
            if (isset($result['errors']) && $result['errors'] > 0) {
                $this->warn("⚠️  Errors: {$result['errors']}");
            }
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("❌ Data import failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Detect file format based on extension
     */
    private function detectFormat($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'json':
                return 'json';
            case 'csv':
                return 'csv';
            case 'sql':
                return 'sql';
            default:
                // Try to detect by content
                $content = file_get_contents($filePath, false, null, 0, 1000);
                if (strpos($content, '{') === 0 || strpos($content, '[') === 0) {
                    return 'json';
                } elseif (strpos($content, 'CREATE TABLE') !== false || strpos($content, 'INSERT INTO') !== false) {
                    return 'sql';
                } else {
                    return 'csv';
                }
        }
    }
    
    /**
     * Import data from JSON format
     */
    private function importFromJson($filePath, $truncate, $ignoreErrors)
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        $totalTables = 0;
        $totalRecords = 0;
        $errors = 0;
        
        // Remove metadata if present
        if (isset($data['_metadata'])) {
            unset($data['_metadata']);
        }
        
        foreach ($data as $tableName => $records) {
            if (!is_array($records)) {
                continue;
            }
            
            $this->info("📄 Importing table: {$tableName}");
            
            try {
                // Check if table exists
                if (!Schema::hasTable($tableName)) {
                    $this->warn("   ⚠️  Table {$tableName} does not exist, skipping...");
                    continue;
                }
                
                // Truncate if requested
                if ($truncate) {
                    DB::table($tableName)->truncate();
                    $this->info("   🗑️  Truncated table {$tableName}");
                }
                
                // Import records in chunks
                $chunks = array_chunk($records, 100);
                $recordCount = 0;
                
                foreach ($chunks as $chunk) {
                    try {
                        DB::table($tableName)->insert($chunk);
                        $recordCount += count($chunk);
                    } catch (Exception $e) {
                        if (!$ignoreErrors) {
                            throw $e;
                        }
                        $errors++;
                        $this->warn("   ⚠️  Error inserting chunk: " . $e->getMessage());
                    }
                }
                
                $totalTables++;
                $totalRecords += $recordCount;
                $this->info("   ✓ {$tableName}: {$recordCount} records imported");
                
            } catch (Exception $e) {
                if (!$ignoreErrors) {
                    throw $e;
                }
                $errors++;
                $this->error("   ❌ Error importing {$tableName}: " . $e->getMessage());
            }
        }
        
        return [
            'tables' => $totalTables,
            'records' => $totalRecords,
            'errors' => $errors
        ];
    }
    
    /**
     * Import data from CSV format (directory with multiple CSV files)
     */
    private function importFromCsv($filePath, $truncate, $ignoreErrors)
    {
        // Check if it's a directory or single file
        if (is_dir($filePath)) {
            return $this->importFromCsvDirectory($filePath, $truncate, $ignoreErrors);
        } else {
            return $this->importFromCsvFile($filePath, $truncate, $ignoreErrors);
        }
    }
    
    /**
     * Import from CSV directory
     */
    private function importFromCsvDirectory($dirPath, $truncate, $ignoreErrors)
    {
        $csvFiles = glob($dirPath . '/*.csv');
        $totalTables = 0;
        $totalRecords = 0;
        $errors = 0;
        
        foreach ($csvFiles as $csvFile) {
            $tableName = pathinfo($csvFile, PATHINFO_FILENAME);
            
            if ($tableName === 'metadata') {
                continue; // Skip metadata file
            }
            
            $this->info("📄 Importing table: {$tableName}");
            
            try {
                $result = $this->importCsvFile($csvFile, $tableName, $truncate, $ignoreErrors);
                $totalTables++;
                $totalRecords += $result['records'];
                $errors += $result['errors'];
            } catch (Exception $e) {
                if (!$ignoreErrors) {
                    throw $e;
                }
                $errors++;
                $this->error("   ❌ Error importing {$tableName}: " . $e->getMessage());
            }
        }
        
        return [
            'tables' => $totalTables,
            'records' => $totalRecords,
            'errors' => $errors
        ];
    }
    
    /**
     * Import from single CSV file
     */
    private function importFromCsvFile($filePath, $truncate, $ignoreErrors)
    {
        $tableName = $this->ask('Enter table name for this CSV file:');
        
        if (!$tableName) {
            throw new Exception('Table name is required for CSV import');
        }
        
        $result = $this->importCsvFile($filePath, $tableName, $truncate, $ignoreErrors);
        
        return [
            'tables' => 1,
            'records' => $result['records'],
            'errors' => $result['errors']
        ];
    }
    
    /**
     * Import single CSV file
     */
    private function importCsvFile($csvFile, $tableName, $truncate, $ignoreErrors)
    {
        if (!Schema::hasTable($tableName)) {
            throw new Exception("Table {$tableName} does not exist");
        }
        
        // Truncate if requested
        if ($truncate) {
            DB::table($tableName)->truncate();
            $this->info("   🗑️  Truncated table {$tableName}");
        }
        
        $handle = fopen($csvFile, 'r');
        $headers = fgetcsv($handle);
        $records = 0;
        $errors = 0;
        $batch = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $batch[] = array_combine($headers, $row);
                
                // Insert in batches of 100
                if (count($batch) >= 100) {
                    try {
                        DB::table($tableName)->insert($batch);
                        $records += count($batch);
                    } catch (Exception $e) {
                        if (!$ignoreErrors) {
                            throw $e;
                        }
                        $errors++;
                        $this->warn("   ⚠️  Error inserting batch: " . $e->getMessage());
                    }
                    $batch = [];
                }
            }
        }
        
        // Insert remaining records
        if (!empty($batch)) {
            try {
                DB::table($tableName)->insert($batch);
                $records += count($batch);
            } catch (Exception $e) {
                if (!$ignoreErrors) {
                    throw $e;
                }
                $errors++;
                $this->warn("   ⚠️  Error inserting final batch: " . $e->getMessage());
            }
        }
        
        fclose($handle);
        $this->info("   ✓ {$tableName}: {$records} records imported");
        
        return [
            'records' => $records,
            'errors' => $errors
        ];
    }
    
    /**
     * Import data from SQL format
     */
    private function importFromSql($filePath, $ignoreErrors)
    {
        $content = file_get_contents($filePath);
        
        // Split SQL statements
        $statements = array_filter(
            array_map('trim', explode(';', $content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $totalStatements = count($statements);
        $executed = 0;
        $errors = 0;
        
        $this->info("📄 Executing {$totalStatements} SQL statements...");
        
        foreach ($statements as $statement) {
            try {
                DB::unprepared($statement);
                $executed++;
            } catch (Exception $e) {
                if (!$ignoreErrors) {
                    throw $e;
                }
                $errors++;
                $this->warn("   ⚠️  Error executing statement: " . $e->getMessage());
            }
        }
        
        $this->info("   ✓ Executed {$executed} statements");
        
        return [
            'tables' => 'N/A',
            'records' => $executed,
            'errors' => $errors
        ];
    }
}
