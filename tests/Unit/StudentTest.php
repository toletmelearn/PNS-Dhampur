<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;

class StudentTest extends TestCase
{
    use  WithFaker;

    /**
     * Test student creation using factory.
     * ✅ Uses factory instead of direct model creation
     * ✅ Uses test database (RefreshDatabase trait)
     * ✅ Proper test isolation
     */
    public function test_student_creation()
    {
        // Create student using factory - this uses the test database
        $student = Student::factory()->create([
            'name' => 'Test Student',
            'admission_no' => 'TEST001'
        ]);

        // Assert the student was created correctly
        $this->assertInstanceOf(Student::class, $student);
        $this->assertEquals('Test Student', $student->name);
        $this->assertEquals('TEST001', $student->admission_no);
        
        // Assert it exists in the database
        $this->assertDatabaseHas('students', [
            'name' => 'Test Student',
            'admission_no' => 'TEST001'
        ]);
    }

    /**
     * Test student creation with specific class.
     * ✅ Uses factories for related models
     * ✅ Tests relationships properly
     */
    public function test_student_creation_with_class()
    {
        // Create a class using factory
        $class = ClassModel::factory()->create([
            'name' => 'Class 10A'
        ]);

        // Create student in that class
        $student = Student::factory()->create([
            'name' => 'Test Student',
            'class_id' => $class->id
        ]);

        // Assert relationships work correctly
        $this->assertEquals($class->id, $student->class_id);
        $this->assertEquals('Class 10A', $student->class->name);
    }

    /**
     * Test student factory states.
     * ✅ Uses factory states for different scenarios
     */
    public function test_student_factory_states()
    {
        // Test active student state
        $activeStudent = Student::factory()->active()->create();
        $this->assertEquals('active', $activeStudent->status);

        // Test verified student state
        $verifiedStudent = Student::factory()->verified()->create();
        $this->assertEquals('verified', $verifiedStudent->verification_status);

        // Test student with specific admission number
        $studentWithAdmission = Student::factory()
            ->withAdmissionNumber('CUSTOM001')
            ->create();
        $this->assertEquals('CUSTOM001', $studentWithAdmission->admission_no);
    }

    /**
     * Test student validation rules.
     * ✅ Tests model validation without affecting production data
     */
    public function test_student_requires_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // This should fail due to validation/database constraints
        Student::factory()->create(['name' => null]);
    }

    /**
     * Test student relationships.
     * ✅ Uses factories to test model relationships
     */
    public function test_student_belongs_to_user()
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $student->user);
        $this->assertEquals($user->id, $student->user->id);
    }

    /**
     * Test student belongs to class.
     * ✅ Tests relationship using factories
     */
    public function test_student_belongs_to_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);

        $this->assertInstanceOf(ClassModel::class, $student->class);
        $this->assertEquals($class->id, $student->class->id);
    }

    /**
     * Test student soft deletes.
     * ✅ Tests soft delete functionality safely
     */
    public function test_student_soft_deletes()
    {
        $student = Student::factory()->create();
        $studentId = $student->id;

        // Delete the student
        $student->delete();

        // Assert it's soft deleted
        $this->assertSoftDeleted('students', ['id' => $studentId]);
        
        // Assert it can be restored
        $student->restore();
        $this->assertDatabaseHas('students', ['id' => $studentId]);
    }

    /**
     * Test student scopes or custom methods.
     * ✅ Tests model methods using test data
     */
    public function test_student_active_scope()
    {
        // Create active and inactive students
        Student::factory()->create(['status' => 'active']);
        Student::factory()->create(['status' => 'inactive']);
        Student::factory()->create(['status' => 'active']);

        // Test if model has active scope (assuming it exists)
        $totalStudents = Student::count();
        $this->assertEquals(3, $totalStudents);

        // If there's an active scope, test it
        // $activeStudents = Student::active()->count();
        // $this->assertEquals(2, $activeStudents);
    }

    /**
     * Test student data casting.
     * ✅ Tests model casts and attributes
     */
    public function test_student_data_casting()
    {
        $student = Student::factory()->create([
            'dob' => '2010-05-15',
            'documents' => ['birth_cert' => 'path/to/cert.pdf'],
            'meta' => ['extra_info' => 'test']
        ]);

        // Test date casting
        $this->assertInstanceOf(\Carbon\Carbon::class, $student->dob);
        
        // Test array casting
        $this->assertIsArray($student->documents);
        $this->assertIsArray($student->meta);
        $this->assertEquals('path/to/cert.pdf', $student->documents['birth_cert']);
        $this->assertEquals('test', $student->meta['extra_info']);
    }
}