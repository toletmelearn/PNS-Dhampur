<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\Salary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class SoftDeleteSecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $teacher;
    protected $student;
    protected $classModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        
        // Create test class
        $this->classModel = ClassModel::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
    }

    /** @test */
    public function test_student_soft_delete_functionality()
    {
        $student = Student::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Student',
            'class_id' => $this->classModel->id
        ]);

        // Verify student exists
        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertNull($student->deleted_at);

        // Soft delete the student
        $student->delete();

        // Verify soft delete
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertNotNull($student->fresh()->deleted_at);

        // Verify student is not in normal queries
        $this->assertNull(Student::find($student->id));
        $this->assertNotNull(Student::withTrashed()->find($student->id));

        // Test restore
        $student->restore();
        $this->assertNull($student->fresh()->deleted_at);
        $this->assertNotNull(Student::find($student->id));
    }

    /** @test */
    public function test_teacher_soft_delete_functionality()
    {
        $teacher = Teacher::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'user_id' => $this->teacher->id
        ]);

        // Soft delete the teacher
        $teacher->delete();

        // Verify soft delete
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
        $this->assertNotNull($teacher->fresh()->deleted_at);

        // Verify teacher is not in normal queries
        $this->assertNull(Teacher::find($teacher->id));
        $this->assertNotNull(Teacher::withTrashed()->find($teacher->id));

        // Test restore
        $teacher->restore();
        $this->assertNull($teacher->fresh()->deleted_at);
    }

    /** @test */
    public function test_user_soft_delete_functionality()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'role' => 'student'
        ]);

        // Soft delete the user
        $user->delete();

        // Verify soft delete
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotNull($user->fresh()->deleted_at);

        // Verify user is not in normal queries
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    /** @test */
    public function test_exam_soft_delete_functionality()
    {
        $exam = Exam::factory()->create([
            'name' => 'Test Exam',
            'class_id' => $this->classModel->id,
            'exam_date' => Carbon::now()->addDays(7)
        ]);

        // Soft delete the exam
        $exam->delete();

        // Verify soft delete
        $this->assertSoftDeleted('exams', ['id' => $exam->id]);
        $this->assertNotNull($exam->fresh()->deleted_at);

        // Verify exam is not in normal queries
        $this->assertNull(Exam::find($exam->id));
        $this->assertNotNull(Exam::withTrashed()->find($exam->id));
    }

    /** @test */
    public function test_fee_soft_delete_functionality()
    {
        $fee = Fee::factory()->create([
            'student_id' => Student::factory()->create(['class_id' => $this->classModel->id])->id,
            'amount' => 1000,
            'due_date' => Carbon::now()->addDays(30)
        ]);

        // Soft delete the fee
        $fee->delete();

        // Verify soft delete
        $this->assertSoftDeleted('fees', ['id' => $fee->id]);
        $this->assertNotNull($fee->fresh()->deleted_at);

        // Verify fee is not in normal queries
        $this->assertNull(Fee::find($fee->id));
        $this->assertNotNull(Fee::withTrashed()->find($fee->id));
    }

    /** @test */
    public function test_subject_soft_delete_functionality()
    {
        $subject = Subject::factory()->create([
            'name' => 'Test Subject',
            'code' => 'TS001',
            'class_id' => $this->classModel->id
        ]);

        // Soft delete the subject
        $subject->delete();

        // Verify soft delete
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
        $this->assertNotNull($subject->fresh()->deleted_at);

        // Verify subject is not in normal queries
        $this->assertNull(Subject::find($subject->id));
        $this->assertNotNull(Subject::withTrashed()->find($subject->id));
    }

    /** @test */
    public function test_attendance_soft_delete_functionality()
    {
        $student = Student::factory()->create(['class_id' => $this->classModel->id]);
        
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'date' => Carbon::today(),
            'status' => 'present'
        ]);

        // Soft delete the attendance
        $attendance->delete();

        // Verify soft delete
        $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
        $this->assertNotNull($attendance->fresh()->deleted_at);

        // Verify attendance is not in normal queries
        $this->assertNull(Attendance::find($attendance->id));
        $this->assertNotNull(Attendance::withTrashed()->find($attendance->id));
    }

    /** @test */
    public function test_result_soft_delete_functionality()
    {
        $student = Student::factory()->create(['class_id' => $this->classModel->id]);
        $exam = Exam::factory()->create(['class_id' => $this->classModel->id]);
        
        $result = Result::factory()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'subject' => 'Mathematics',
            'marks' => 85,
            'grade' => 'A'
        ]);

        // Soft delete the result
        $result->delete();

        // Verify soft delete
        $this->assertSoftDeleted('results', ['id' => $result->id]);
        $this->assertNotNull($result->fresh()->deleted_at);

        // Verify result is not in normal queries
        $this->assertNull(Result::find($result->id));
        $this->assertNotNull(Result::withTrashed()->find($result->id));
    }

    /** @test */
    public function test_salary_soft_delete_functionality()
    {
        $teacher = Teacher::factory()->create(['user_id' => $this->teacher->id]);
        
        $salary = Salary::factory()->create([
            'teacher_id' => $teacher->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'basic_salary' => 50000,
            'net_salary' => 45000
        ]);

        // Soft delete the salary
        $salary->delete();

        // Verify soft delete
        $this->assertSoftDeleted('salaries', ['id' => $salary->id]);
        $this->assertNotNull($salary->fresh()->deleted_at);

        // Verify salary is not in normal queries
        $this->assertNull(Salary::find($salary->id));
        $this->assertNotNull(Salary::withTrashed()->find($salary->id));
    }

    /** @test */
    public function test_force_delete_functionality()
    {
        $student = Student::factory()->create(['class_id' => $this->classModel->id]);
        $studentId = $student->id;

        // Soft delete first
        $student->delete();
        $this->assertSoftDeleted('students', ['id' => $studentId]);

        // Force delete (permanent deletion)
        $student->forceDelete();
        $this->assertDatabaseMissing('students', ['id' => $studentId]);
        $this->assertNull(Student::withTrashed()->find($studentId));
    }

    /** @test */
    public function test_only_trashed_query_scope()
    {
        $student1 = Student::factory()->create(['class_id' => $this->classModel->id]);
        $student2 = Student::factory()->create(['class_id' => $this->classModel->id]);

        // Delete one student
        $student1->delete();

        // Test query scopes
        $this->assertEquals(1, Student::count()); // Only non-deleted
        $this->assertEquals(2, Student::withTrashed()->count()); // All records
        $this->assertEquals(1, Student::onlyTrashed()->count()); // Only deleted

        // Verify correct records are returned
        $this->assertTrue(Student::onlyTrashed()->first()->is($student1));
        $this->assertTrue(Student::first()->is($student2));
    }

    /** @test */
    public function test_cascade_soft_delete_behavior()
    {
        $student = Student::factory()->create(['class_id' => $this->classModel->id]);
        
        // Create related records
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id
        ]);
        
        $fee = Fee::factory()->create(['student_id' => $student->id]);

        // Soft delete student
        $student->delete();

        // Verify student is soft deleted
        $this->assertSoftDeleted('students', ['id' => $student->id]);

        // Related records should still exist (no cascade delete)
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
        $this->assertDatabaseHas('fees', ['id' => $fee->id]);
        $this->assertNull($attendance->fresh()->deleted_at);
        $this->assertNull($fee->fresh()->deleted_at);
    }

    /** @test */
    public function test_soft_delete_with_relationships()
    {
        $teacher = Teacher::factory()->create(['user_id' => $this->teacher->id]);
        $salary = Salary::factory()->create(['teacher_id' => $teacher->id]);

        // Soft delete teacher
        $teacher->delete();

        // Verify teacher is soft deleted but salary remains
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseHas('salaries', ['id' => $salary->id]);
        
        // Verify relationship still works with trashed records
        $this->assertNotNull(Teacher::withTrashed()->find($teacher->id)->salaries);
    }

    /** @test */
    public function test_restore_functionality()
    {
        $exam = Exam::factory()->create(['class_id' => $this->classModel->id]);
        
        // Delete and verify
        $exam->delete();
        $this->assertSoftDeleted('exams', ['id' => $exam->id]);
        
        // Restore and verify
        $exam->restore();
        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'deleted_at' => null
        ]);
        
        // Verify it's accessible in normal queries again
        $this->assertNotNull(Exam::find($exam->id));
    }

    /** @test */
    public function test_multiple_soft_deletes_and_restores()
    {
        $subjects = Subject::factory()->count(3)->create(['class_id' => $this->classModel->id]);
        
        // Delete all subjects
        foreach ($subjects as $subject) {
            $subject->delete();
        }
        
        // Verify all are soft deleted
        $this->assertEquals(0, Subject::count());
        $this->assertEquals(3, Subject::onlyTrashed()->count());
        
        // Restore one subject
        $subjects[0]->restore();
        
        // Verify counts
        $this->assertEquals(1, Subject::count());
        $this->assertEquals(2, Subject::onlyTrashed()->count());
        $this->assertEquals(3, Subject::withTrashed()->count());
    }

    /** @test */
    public function test_soft_delete_timestamps()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $beforeDelete = Carbon::now();
        $user->delete();
        $afterDelete = Carbon::now();
        
        $deletedAt = $user->fresh()->deleted_at;
        
        $this->assertNotNull($deletedAt);
        $this->assertTrue($deletedAt->between($beforeDelete, $afterDelete));
        
        // Test restore clears timestamp
        $user->restore();
        $this->assertNull($user->fresh()->deleted_at);
    }
}