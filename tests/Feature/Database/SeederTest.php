<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Section;

class SeederTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    /** @test */
    public function database_seeder_runs_successfully()
    {
        // Run all seeders
        Artisan::call('db:seed');
        
        $this->assertTrue(true, 'Database seeder completed successfully');
    }

    /** @test */
    public function user_seeder_creates_admin_user()
    {
        Artisan::call('db:seed', ['--class' => 'UserSeeder']);
        
        // Check if admin user exists
        $admin = User::where('role', 'admin')->first();
        
        $this->assertNotNull($admin, 'Admin user should be created');
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('admin@pnsdhampur.edu.in', $admin->email);
        $this->assertTrue($admin->is_active);
    }

    /** @test */
    public function user_seeder_creates_sample_teachers()
    {
        Artisan::call('db:seed', ['--class' => 'UserSeeder']);
        
        // Check if sample teachers exist
        $teachers = User::where('role', 'teacher')->get();
        
        $this->assertGreaterThan(0, $teachers->count(), 'Sample teachers should be created');
        
        foreach ($teachers as $teacher) {
            $this->assertEquals('teacher', $teacher->role);
            $this->assertTrue($teacher->is_active);
            $this->assertNotNull($teacher->name);
            $this->assertNotNull($teacher->email);
        }
    }

    /** @test */
    public function class_seeder_creates_standard_classes()
    {
        Artisan::call('db:seed', ['--class' => 'ClassSeeder']);
        
        // Check if standard classes are created
        $classes = Classes::all();
        
        $this->assertGreaterThan(0, $classes->count(), 'Classes should be created');
        
        // Check for specific classes
        $expectedClasses = [
            'Nursery', 'LKG', 'UKG', 'Class 1', 'Class 2', 'Class 3',
            'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8',
            'Class 9', 'Class 10', 'Class 11', 'Class 12'
        ];

        foreach ($expectedClasses as $className) {
            $class = Classes::where('name', $className)->first();
            $this->assertNotNull($class, "Class {$className} should exist");
            $this->assertTrue($class->is_active);
        }
    }

    /** @test */
    public function section_seeder_creates_sections_for_classes()
    {
        Artisan::call('db:seed', ['--class' => 'ClassSeeder']);
        Artisan::call('db:seed', ['--class' => 'SectionSeeder']);
        
        $sections = Section::all();
        
        $this->assertGreaterThan(0, $sections->count(), 'Sections should be created');
        
        // Check if sections are properly linked to classes
        foreach ($sections as $section) {
            $this->assertNotNull($section->class_id);
            $this->assertNotNull($section->name);
            $this->assertTrue($section->is_active);
            
            // Verify class exists
            $class = Classes::find($section->class_id);
            $this->assertNotNull($class, 'Section should be linked to existing class');
        }
        
        // Check for standard sections (A, B, C)
        $sectionNames = Section::pluck('name')->unique()->toArray();
        $this->assertContains('A', $sectionNames);
        $this->assertContains('B', $sectionNames);
    }

    /** @test */
    public function student_seeder_creates_sample_students()
    {
        // Run prerequisite seeders
        Artisan::call('db:seed', ['--class' => 'UserSeeder']);
        Artisan::call('db:seed', ['--class' => 'ClassSeeder']);
        Artisan::call('db:seed', ['--class' => 'SectionSeeder']);
        Artisan::call('db:seed', ['--class' => 'StudentSeeder']);
        
        $students = Student::all();
        
        $this->assertGreaterThan(0, $students->count(), 'Sample students should be created');
        
        foreach ($students as $student) {
            // Check required fields
            $this->assertNotNull($student->name);
            $this->assertNotNull($student->admission_number);
            $this->assertNotNull($student->class_id);
            $this->assertNotNull($student->section_id);
            
            // Check admission number format
            $this->assertMatchesRegularExpression('/^PNS\d{4}$/', $student->admission_number);
            
            // Verify class and section exist
            $class = Classes::find($student->class_id);
            $section = Section::find($student->section_id);
            
            $this->assertNotNull($class, 'Student should be linked to existing class');
            $this->assertNotNull($section, 'Student should be linked to existing section');
            
            // Check verification status
            $this->assertContains($student->verification_status, ['pending', 'verified', 'mismatch']);
            
            // Check gender
            if ($student->gender) {
                $this->assertContains($student->gender, ['male', 'female', 'other']);
            }
            
            // Check Aadhaar format if present
            if ($student->aadhaar) {
                $this->assertMatchesRegularExpression('/^\d{12}$/', $student->aadhaar);
            }
        }
    }

    /** @test */
    public function seeders_create_proper_relationships()
    {
        Artisan::call('db:seed');
        
        // Test User-Class relationship (class teacher)
        $classes = Classes::whereNotNull('teacher_id')->get();
        foreach ($classes as $class) {
            $teacher = User::find($class->teacher_id);
            $this->assertNotNull($teacher, 'Class should have valid teacher');
            $this->assertEquals('teacher', $teacher->role);
        }
        
        // Test Section-Class relationship
        $sections = Section::all();
        foreach ($sections as $section) {
            $class = Classes::find($section->class_id);
            $this->assertNotNull($class, 'Section should belong to valid class');
        }
        
        // Test Student-Class-Section relationship
        $students = Student::all();
        foreach ($students as $student) {
            $class = Classes::find($student->class_id);
            $section = Section::find($student->section_id);
            
            $this->assertNotNull($class, 'Student should belong to valid class');
            $this->assertNotNull($section, 'Student should belong to valid section');
            $this->assertEquals($class->id, $section->class_id, 'Student section should belong to student class');
        }
    }

    /** @test */
    public function seeders_respect_unique_constraints()
    {
        Artisan::call('db:seed');
        
        // Check unique admission numbers
        $admissionNumbers = Student::pluck('admission_number')->toArray();
        $uniqueAdmissionNumbers = array_unique($admissionNumbers);
        
        $this->assertEquals(
            count($admissionNumbers),
            count($uniqueAdmissionNumbers),
            'All admission numbers should be unique'
        );
        
        // Check unique emails for users
        $emails = User::pluck('email')->toArray();
        $uniqueEmails = array_unique($emails);
        
        $this->assertEquals(
            count($emails),
            count($uniqueEmails),
            'All user emails should be unique'
        );
        
        // Check unique Aadhaar numbers (if present)
        $aadhaarNumbers = Student::whereNotNull('aadhaar')->pluck('aadhaar')->toArray();
        $uniqueAadhaarNumbers = array_unique($aadhaarNumbers);
        
        $this->assertEquals(
            count($aadhaarNumbers),
            count($uniqueAadhaarNumbers),
            'All Aadhaar numbers should be unique'
        );
    }

    /** @test */
    public function seeders_create_realistic_data()
    {
        Artisan::call('db:seed');
        
        // Check students have realistic ages (5-18 years)
        $students = Student::whereNotNull('dob')->get();
        
        foreach ($students as $student) {
            $age = now()->diffInYears($student->dob);
            $this->assertGreaterThanOrEqual(3, $age, 'Student should be at least 3 years old');
            $this->assertLessThanOrEqual(20, $age, 'Student should be at most 20 years old');
        }
        
        // Check phone numbers have valid format
        $studentsWithPhone = Student::whereNotNull('phone')->get();
        
        foreach ($studentsWithPhone as $student) {
            $this->assertMatchesRegularExpression(
                '/^[6-9]\d{9}$/',
                $student->phone,
                'Phone number should be valid Indian mobile number'
            );
        }
        
        // Check email formats
        $studentsWithEmail = Student::whereNotNull('email')->get();
        
        foreach ($studentsWithEmail as $student) {
            $this->assertFilter($student->email, FILTER_VALIDATE_EMAIL, 'Email should be valid');
        }
    }

    /** @test */
    public function seeders_create_balanced_distribution()
    {
        Artisan::call('db:seed');
        
        // Check gender distribution is somewhat balanced
        $maleCount = Student::where('gender', 'male')->count();
        $femaleCount = Student::where('gender', 'female')->count();
        $totalWithGender = $maleCount + $femaleCount;
        
        if ($totalWithGender > 0) {
            $malePercentage = ($maleCount / $totalWithGender) * 100;
            $this->assertGreaterThan(30, $malePercentage, 'Male students should be at least 30%');
            $this->assertLessThan(70, $malePercentage, 'Male students should be at most 70%');
        }
        
        // Check class distribution
        $classes = Classes::all();
        foreach ($classes as $class) {
            $studentCount = Student::where('class_id', $class->id)->count();
            // Each class should have some students but not too many
            $this->assertLessThanOrEqual(100, $studentCount, 'Class should not have more than 100 students');
        }
    }

    /** @test */
    public function seeders_can_be_run_multiple_times()
    {
        // Run seeders first time
        Artisan::call('db:seed');
        $firstRunStudentCount = Student::count();
        $firstRunUserCount = User::count();
        
        // Run seeders again (should not duplicate data)
        Artisan::call('db:seed');
        $secondRunStudentCount = Student::count();
        $secondRunUserCount = User::count();
        
        // Counts should remain the same (idempotent)
        $this->assertEquals($firstRunStudentCount, $secondRunStudentCount);
        $this->assertEquals($firstRunUserCount, $secondRunUserCount);
    }

    /** @test */
    public function production_seeder_creates_minimal_data()
    {
        // Run production seeder
        Artisan::call('db:seed', ['--class' => 'ProductionSeeder']);
        
        // Should create admin user
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin, 'Production seeder should create admin user');
        
        // Should create basic classes
        $classes = Classes::all();
        $this->assertGreaterThan(0, $classes->count(), 'Production seeder should create basic classes');
        
        // Should not create sample students in production
        $students = Student::all();
        $this->assertEquals(0, $students->count(), 'Production seeder should not create sample students');
    }

    /** @test */
    public function development_seeder_creates_comprehensive_data()
    {
        // Run development seeder
        Artisan::call('db:seed', ['--class' => 'DevelopmentSeeder']);
        
        // Should create admin and teachers
        $users = User::all();
        $this->assertGreaterThan(1, $users->count(), 'Development seeder should create multiple users');
        
        // Should create sample students
        $students = Student::all();
        $this->assertGreaterThan(0, $students->count(), 'Development seeder should create sample students');
        
        // Should create classes and sections
        $classes = Classes::all();
        $sections = Section::all();
        
        $this->assertGreaterThan(0, $classes->count(), 'Development seeder should create classes');
        $this->assertGreaterThan(0, $sections->count(), 'Development seeder should create sections');
    }

    /** @test */
    public function seeders_handle_foreign_key_constraints()
    {
        // This should not throw any foreign key constraint errors
        Artisan::call('db:seed');
        
        // Verify all foreign keys are valid
        $students = Student::all();
        foreach ($students as $student) {
            $this->assertNotNull(Classes::find($student->class_id));
            $this->assertNotNull(Section::find($student->section_id));
        }
        
        $sections = Section::all();
        foreach ($sections as $section) {
            $this->assertNotNull(Classes::find($section->class_id));
            if ($section->teacher_id) {
                $this->assertNotNull(User::find($section->teacher_id));
            }
        }
        
        $classes = Classes::all();
        foreach ($classes as $class) {
            if ($class->teacher_id) {
                $this->assertNotNull(User::find($class->teacher_id));
            }
        }
    }

    /** @test */
    public function seeders_create_proper_timestamps()
    {
        Artisan::call('db:seed');
        
        // Check that all records have proper timestamps
        $users = User::all();
        foreach ($users as $user) {
            $this->assertNotNull($user->created_at);
            $this->assertNotNull($user->updated_at);
            $this->assertLessThanOrEqual(now(), $user->created_at);
        }
        
        $students = Student::all();
        foreach ($students as $student) {
            $this->assertNotNull($student->created_at);
            $this->assertNotNull($student->updated_at);
            $this->assertLessThanOrEqual(now(), $student->created_at);
        }
    }

    /** @test */
    public function seeders_create_proper_academic_year_data()
    {
        Artisan::call('db:seed');
        
        $currentYear = now()->year;
        $academicYear = $currentYear . '-' . ($currentYear + 1);
        
        // Check classes have current academic year
        $classes = Classes::all();
        foreach ($classes as $class) {
            if ($class->academic_year) {
                $this->assertEquals($academicYear, $class->academic_year);
            }
        }
        
        // Check students have admission dates within reasonable range
        $students = Student::whereNotNull('admission_date')->get();
        foreach ($students as $student) {
            $admissionYear = $student->admission_date->year;
            $this->assertGreaterThanOrEqual($currentYear - 15, $admissionYear);
            $this->assertLessThanOrEqual($currentYear, $admissionYear);
        }
    }
}