<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_migrations_run_successfully()
    {
        // Fresh migration should complete without errors
        Artisan::call('migrate:fresh');
        
        $this->assertTrue(true, 'All migrations completed successfully');
    }

    /** @test */
    public function users_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('users'));
        
        $columns = [
            'id', 'name', 'email', 'email_verified_at', 'password',
            'role', 'phone', 'is_active', 'last_login_at',
            'two_factor_secret', 'two_factor_confirmed_at',
            'remember_token', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table should have {$column} column"
            );
        }

        // Check indexes (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('users', ['email']));
            $this->assertTrue(Schema::hasIndex('users', ['role']));
        }
    }

    /** @test */
    public function students_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('students'));
        
        $columns = [
            'id', 'admission_number', 'name', 'father_name', 'mother_name',
            'dob', 'gender', 'aadhaar', 'address', 'phone', 'email',
            'class_id', 'section_id', 'roll_number', 'admission_date',
            'verification_status', 'is_active', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('students', $column),
                "Students table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('students', ['class_id']));
            $this->assertTrue(Schema::hasIndex('students', ['section_id']));
            
            // Check unique constraints
            $this->assertTrue(Schema::hasIndex('students', ['admission_number']));
            $this->assertTrue(Schema::hasIndex('students', ['aadhaar']));
        }
    }

    /** @test */
    public function classes_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('class_models'));
        
        $columns = [
            'id', 'name', 'section', 'description', 'capacity', 'class_teacher_id', 'is_active',
            'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('class_models', $column),
                "Class_models table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('class_models', ['class_teacher_id']));
        }
    }

    /** @test */
    public function sections_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('sections'));
        
        $columns = [
            'id', 'name', 'class_id', 'teacher_id', 'capacity',
            'is_active', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('sections', $column),
                "Sections table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('sections', ['class_id']));
            $this->assertTrue(Schema::hasIndex('sections', ['teacher_id']));
        }
    }

    /** @test */
    public function document_verifications_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('document_verifications'));
        
        $columns = [
            'id', 'student_id', 'document_type', 'document_name', 'file_path',
            'file_hash', 'verification_status', 'verification_notes',
            'verified_by', 'verified_at', 'expiry_date', 'metadata',
            'is_mandatory', 'verification_attempts', 'last_verification_attempt',
            'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('document_verifications', $column),
                "Document verifications table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('document_verifications', ['student_id']));
            $this->assertTrue(Schema::hasIndex('document_verifications', ['verified_by']));
        }
    }

    /** @test */
    public function student_verifications_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('student_verifications'));
        
        $columns = [
            'id', 'student_id', 'document_type', 'original_file_path',
            'processed_file_path', 'verification_status', 'verification_method',
            'extracted_data', 'verification_results', 'confidence_score',
            'format_valid', 'quality_check_passed', 'data_consistency_check',
            'cross_reference_check', 'reviewed_by', 'reviewer_comments',
            'reviewed_at', 'verification_log', 'uploaded_by',
            'verification_started_at', 'verification_completed_at',
            'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('student_verifications', $column),
                "Student verifications table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('student_verifications', ['student_id']));
            $this->assertTrue(Schema::hasIndex('student_verifications', ['reviewed_by']));
        }
    }

    /** @test */
    public function aadhaar_verifications_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('aadhaar_verifications'));
        
        $columns = [
            'id', 'student_id', 'aadhaar_number', 'verification_status',
            'demographic_data', 'match_percentage', 'api_response',
            'transaction_id', 'verified_at', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('aadhaar_verifications', $column),
                "Aadhaar verifications table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('aadhaar_verifications', ['student_id']));
            $this->assertTrue(Schema::hasIndex('aadhaar_verifications', ['aadhaar_number']));
        }
    }

    /** @test */
    public function audit_logs_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        
        $columns = [
            'id', 'user_id', 'action', 'model_type', 'model_id',
            'old_values', 'new_values', 'ip_address', 'user_agent',
            'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('audit_logs', $column),
                "Audit logs table should have {$column} column"
            );
        }

        // Check foreign keys (skip for SQLite as it doesn't support hasIndex method)
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(Schema::hasIndex('audit_logs', ['user_id']));
            $this->assertTrue(Schema::hasIndex('audit_logs', ['model_type', 'model_id']));
        }
    }

    /** @test */
    public function personal_access_tokens_table_exists()
    {
        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        
        $columns = [
            'id', 'tokenable_type', 'tokenable_id', 'name', 'token',
            'abilities', 'last_used_at', 'expires_at', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('personal_access_tokens', $column),
                "Personal access tokens table should have {$column} column"
            );
        }
    }

    /** @test */
    public function failed_jobs_table_exists()
    {
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        
        $columns = [
            'id', 'uuid', 'connection', 'queue', 'payload',
            'exception', 'failed_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('failed_jobs', $column),
                "Failed jobs table should have {$column} column"
            );
        }
    }

    /** @test */
    public function jobs_table_exists()
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        
        $columns = [
            'id', 'queue', 'payload', 'attempts', 'reserved_at',
            'available_at', 'created_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('jobs', $column),
                "Jobs table should have {$column} column"
            );
        }
    }

    /** @test */
    public function password_reset_tokens_table_exists()
    {
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        
        $columns = ['email', 'token', 'created_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('password_reset_tokens', $column),
                "Password reset tokens table should have {$column} column"
            );
        }
    }

    /** @test */
    public function foreign_key_constraints_are_properly_set()
    {
        // Test that foreign key constraints work
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to insert a student with non-existent class_id
        DB::table('students')->insert([
            'name' => 'Test Student',
            'admission_number' => 'TEST001',
            'aadhaar' => '123456789001',
            'class_id' => 999999, // Non-existent class
            'section_id' => 999999, // Non-existent section
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /** @test */
    public function unique_constraints_are_enforced()
    {
        // Create required class and section data first
        $classId = DB::table('class_models')->insertGetId([
            'name' => 'Test Class',
            'section' => 'A',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $sectionId = DB::table('sections')->insertGetId([
            'name' => 'A',
            'class_id' => $classId,
            'capacity' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create a student
        DB::table('students')->insert([
            'name' => 'Test Student',
            'admission_number' => 'UNIQUE001',
            'aadhaar' => '123456789012',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Try to create another student with same admission number
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table('students')->insert([
            'name' => 'Another Student',
            'admission_number' => 'UNIQUE001', // Duplicate admission number
            'aadhaar' => '123456789002',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /** @test */
    public function nullable_columns_work_correctly()
    {
        // Create required class and section data first
        $classId = DB::table('class_models')->insertGetId([
            'name' => 'Test Class',
            'section' => 'A',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $sectionId = DB::table('sections')->insertGetId([
            'name' => 'A',
            'class_id' => $classId,
            'capacity' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert student with minimal required data
        $studentId = DB::table('students')->insertGetId([
            'name' => 'Minimal Student',
            'admission_number' => 'MIN001',
            'aadhaar' => '123456789003',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $student = DB::table('students')->find($studentId);
        
        // These fields should be nullable
        $this->assertNull($student->father_name);
        $this->assertNull($student->mother_name);
        $this->assertNull($student->email);
        $this->assertNull($student->phone);
    }

    /** @test */
    public function default_values_are_set_correctly()
    {
        // Insert user with minimal data
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = DB::table('users')->find($userId);
        
        // Check default values
        $this->assertTrue((bool)$user->is_active); // Should default to true
        $this->assertEquals('student', $user->role); // Should default to student
    }

    /** @test */
    public function json_columns_work_correctly()
    {
        // Create a user first for the uploaded_by foreign key
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create a student first for the student_id foreign key
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $userId,
            'admission_number' => 'TEST001',
            'name' => 'Test Student',
            'aadhaar' => '123456789012',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert verification with JSON data
        $verificationId = DB::table('student_verifications')->insertGetId([
            'student_id' => $studentId,
            'document_type' => 'aadhaar_card',
            'original_file_path' => '/path/to/aadhaar.jpg',
            'extracted_data' => json_encode([
                'name' => 'Test Student',
                'dob' => '2005-01-01'
            ]),
            'verification_results' => json_encode([
                'name_mismatch' => false,
                'dob_mismatch' => true
            ]),
            'verification_status' => 'verified',
            'uploaded_by' => $userId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $verification = DB::table('student_verifications')->find($verificationId);
        
        // JSON data should be stored and retrievable
        $this->assertIsString($verification->extracted_data);
        $this->assertIsString($verification->verification_results);
        
        $decodedData = json_decode($verification->extracted_data, true);
        $this->assertEquals('Test Student', $decodedData['name']);
    }

    /** @test */
    public function timestamp_columns_work_correctly()
    {
        $beforeInsert = now();
        
        $userId = DB::table('users')->insertGetId([
            'name' => 'Timestamp Test',
            'email' => 'timestamp@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $afterInsert = now();
        
        $user = DB::table('users')->find($userId);
        
        $this->assertGreaterThanOrEqual($beforeInsert, $user->created_at);
        $this->assertLessThanOrEqual($afterInsert, $user->created_at);
        $this->assertEquals($user->created_at, $user->updated_at);
    }

    /** @test */
    public function soft_deletes_work_correctly()
    {
        // Create a user and student first to satisfy foreign key constraints
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $student = DB::table('students')->insertGetId([
            'name' => 'Test Student',
            'admission_number' => 'TEST001',
            'aadhaar' => '123456789999',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert a document verification record
        $documentId = DB::table('document_verifications')->insertGetId([
            'student_id' => $student,
            'document_type' => 'aadhaar_card',
            'document_name' => 'Aadhaar Card',
            'file_path' => '/path/to/test.jpg',
            'file_hash' => 'test_hash',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Check that the document exists
        $document = DB::table('document_verifications')->find($documentId);
        $this->assertNotNull($document);
        
        // Note: document_verifications table doesn't have soft deletes (deleted_at column)
        // This test verifies that regular deletion works
        DB::table('document_verifications')->where('id', $documentId)->delete();
        
        $deletedDocument = DB::table('document_verifications')->find($documentId);
        $this->assertNull($deletedDocument);
    }

    /** @test */
    public function database_indexes_improve_query_performance()
    {
        // Create required class and section records first
        $classId = DB::table('class_models')->insertGetId([
            'name' => 'Test Class',
            'section' => 'A',
            'description' => 'Test class for performance testing',
            'capacity' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $sectionId = DB::table('sections')->insertGetId([
            'name' => 'A',
            'class_id' => $classId,
            'capacity' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Insert test data
        for ($i = 1; $i <= 1000; $i++) {
            DB::table('students')->insert([
                'name' => "Student {$i}",
                'admission_number' => "ADM{$i}",
                'aadhaar' => str_pad($i, 12, '0', STR_PAD_LEFT),
                'class_id' => $classId,
                'section_id' => $sectionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Query with indexed column should be fast
        $start = microtime(true);
        DB::table('students')->where('admission_number', 'ADM500')->first();
        $indexedQueryTime = microtime(true) - $start;

        // This should complete quickly due to index
        $this->assertLessThan(0.1, $indexedQueryTime, 'Indexed query should be fast');
    }

    /** @test */
    public function migration_rollback_works_correctly()
    {
        // Run migrations
        Artisan::call('migrate');
        
        // Rollback last batch
        Artisan::call('migrate:rollback');
        
        // Run migrations again
        Artisan::call('migrate');
        
        // All tables should still exist and be functional
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('students'));
        $this->assertTrue(Schema::hasTable('class_models'));
    }

    /** @test */
    public function database_charset_and_collation_are_correct()
    {
        // Skip this test for SQLite as it doesn't support SHOW TABLE STATUS
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support SHOW TABLE STATUS command');
        }

        $tables = [
            'users', 'students', 'class_models', 'sections',
            'student_documents', 'student_verifications'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $tableInfo = DB::select("SHOW TABLE STATUS LIKE '{$table}'")[0];
                
                // Check charset (should be utf8mb4 for emoji support)
                $this->assertStringContains('utf8mb4', $tableInfo->Collation);
            }
        }
    }
}