<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\StudentService;
use App\Models\Student;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $studentService;
    protected $mockUser;
    protected $mockClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock dependencies
        $this->mockUser = Mockery::mock(User::class);
        $this->mockClass = Mockery::mock(Classes::class);
        
        // Initialize service with mocked dependencies
        $this->studentService = new StudentService(
            $this->mockUser,
            $this->mockClass,
            app('log')
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_student_successfully()
    {
        // Arrange
        $studentData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'admission_no' => 'ADM' . $this->faker->unique()->numberBetween(1000, 9999),
            'class_id' => 1,
            'section_id' => 1,
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'guardian_phone' => $this->faker->phoneNumber,
        ];

        // Create actual models for testing
        $class = Classes::factory()->create();
        $studentData['class_id'] = $class->id;

        // Act
        $result = $this->studentService->createStudent($studentData);

        // Assert
        $this->assertInstanceOf(Student::class, $result);
        $this->assertEquals($studentData['name'], $result->name);
        $this->assertEquals($studentData['email'], $result->email);
        $this->assertEquals($studentData['admission_no'], $result->admission_no);
        $this->assertDatabaseHas('students', [
            'name' => $studentData['name'],
            'email' => $studentData['email'],
            'admission_no' => $studentData['admission_no'],
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_student()
    {
        // Arrange
        $invalidData = [
            'email' => 'invalid-email',
            // Missing required fields
        ];

        // Act & Assert
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->studentService->createStudent($invalidData);
    }

    /** @test */
    public function it_can_update_student_information()
    {
        // Arrange
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '9876543210',
        ];

        // Act
        $result = $this->studentService->updateStudent($student->id, $updateData);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '9876543210',
        ]);
    }

    /** @test */
    public function it_can_delete_student_safely()
    {
        // Arrange
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);

        // Act
        $result = $this->studentService->deleteStudent($student->id);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    /** @test */
    public function it_can_search_students_by_name()
    {
        // Arrange
        $class = Classes::factory()->create();
        $student1 = Student::factory()->create([
            'name' => 'John Doe',
            'class_id' => $class->id
        ]);
        $student2 = Student::factory()->create([
            'name' => 'Jane Smith',
            'class_id' => $class->id
        ]);

        // Act
        $results = $this->studentService->searchStudents('John');

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    /** @test */
    public function it_can_get_students_by_class()
    {
        // Arrange
        $class1 = Classes::factory()->create();
        $class2 = Classes::factory()->create();
        
        $student1 = Student::factory()->create(['class_id' => $class1->id]);
        $student2 = Student::factory()->create(['class_id' => $class1->id]);
        $student3 = Student::factory()->create(['class_id' => $class2->id]);

        // Act
        $results = $this->studentService->getStudentsByClass($class1->id);

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($student1));
        $this->assertTrue($results->contains($student2));
        $this->assertFalse($results->contains($student3));
    }

    /** @test */
    public function it_can_generate_unique_admission_number()
    {
        // Act
        $admissionNo1 = $this->studentService->generateAdmissionNumber();
        $admissionNo2 = $this->studentService->generateAdmissionNumber();

        // Assert
        $this->assertNotEmpty($admissionNo1);
        $this->assertNotEmpty($admissionNo2);
        $this->assertNotEquals($admissionNo1, $admissionNo2);
        $this->assertStringStartsWith('ADM', $admissionNo1);
        $this->assertStringStartsWith('ADM', $admissionNo2);
    }

    /** @test */
    public function it_can_validate_student_data()
    {
        // Arrange
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'admission_no' => 'ADM1234',
            'class_id' => 1,
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
        ];

        $invalidData = [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email
            'admission_no' => '', // Empty admission number
        ];

        // Act & Assert
        $this->assertTrue($this->studentService->validateStudentData($validData));
        $this->assertFalse($this->studentService->validateStudentData($invalidData));
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Arrange
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'admission_no' => 'ADM1234',
            'class_id' => 999999, // Non-existent class ID
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->studentService->createStudent($studentData);
    }

    /** @test */
    public function it_can_get_student_statistics()
    {
        // Arrange
        $class = Classes::factory()->create();
        Student::factory()->count(5)->create([
            'class_id' => $class->id,
            'gender' => 'male'
        ]);
        Student::factory()->count(3)->create([
            'class_id' => $class->id,
            'gender' => 'female'
        ]);

        // Act
        $stats = $this->studentService->getStudentStatistics();

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_students', $stats);
        $this->assertArrayHasKey('male_students', $stats);
        $this->assertArrayHasKey('female_students', $stats);
        $this->assertEquals(8, $stats['total_students']);
        $this->assertEquals(5, $stats['male_students']);
        $this->assertEquals(3, $stats['female_students']);
    }

    /** @test */
    public function it_can_bulk_import_students()
    {
        // Arrange
        $class = Classes::factory()->create();
        $studentsData = [
            [
                'name' => 'Student 1',
                'email' => 'student1@example.com',
                'admission_no' => 'ADM001',
                'class_id' => $class->id,
                'gender' => 'male',
                'date_of_birth' => '2010-01-01',
            ],
            [
                'name' => 'Student 2',
                'email' => 'student2@example.com',
                'admission_no' => 'ADM002',
                'class_id' => $class->id,
                'gender' => 'female',
                'date_of_birth' => '2010-02-01',
            ],
        ];

        // Act
        $result = $this->studentService->bulkImportStudents($studentsData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['imported_count']);
        $this->assertDatabaseHas('students', ['name' => 'Student 1']);
        $this->assertDatabaseHas('students', ['name' => 'Student 2']);
    }
}