<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExamService;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Classes;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class ExamServiceTest extends TestCase
{
    use  WithFaker;

    protected $examService;
    protected $student;
    protected $class;
    protected $subject;
    protected $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->examService = new ExamService();
        
        // Create test data
        $this->class = Classes::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
        
        $this->student = Student::factory()->create([
            'class_id' => $this->class->id,
            'name' => 'Test Student',
            'admission_no' => 'TEST001'
        ]);

        $this->subject = Subject::factory()->create([
            'name' => 'Mathematics',
            'code' => 'MATH101'
        ]);

        $this->exam = Exam::factory()->create([
            'name' => 'Mid Term Exam',
            'class_id' => $this->class->id,
            'exam_date' => Carbon::today()->addDays(7),
            'total_marks' => 100
        ]);
    }

    /** @test */
    public function it_creates_exam_successfully()
    {
        // Arrange
        $examData = [
            'name' => 'Final Exam',
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'exam_date' => Carbon::today()->addDays(14)->format('Y-m-d'),
            'total_marks' => 100,
            'passing_marks' => 40,
            'duration' => 180, // 3 hours in minutes
            'instructions' => 'Read all questions carefully'
        ];

        // Act
        $result = $this->examService->createExam($examData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('exams', [
            'name' => 'Final Exam',
            'class_id' => $this->class->id,
            'total_marks' => 100
        ]);
    }

    /** @test */
    public function it_validates_exam_date_is_not_in_past()
    {
        // Arrange
        $examData = [
            'name' => 'Past Exam',
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'exam_date' => Carbon::yesterday()->format('Y-m-d'),
            'total_marks' => 100
        ];

        // Act
        $result = $this->examService->createExam($examData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('past', $result['message']);
    }

    /** @test */
    public function it_records_exam_result_successfully()
    {
        // Arrange
        $resultData = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 85,
            'total_marks' => 100,
            'grade' => 'A',
            'remarks' => 'Excellent performance'
        ];

        // Act
        $result = $this->examService->recordExamResult($resultData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'marks_obtained' => 85
        ]);
    }

    /** @test */
    public function it_calculates_grade_correctly()
    {
        // Test grade calculation based on percentage
        $testCases = [
            ['marks' => 95, 'total' => 100, 'expected' => 'A+'],
            ['marks' => 85, 'total' => 100, 'expected' => 'A'],
            ['marks' => 75, 'total' => 100, 'expected' => 'B+'],
            ['marks' => 65, 'total' => 100, 'expected' => 'B'],
            ['marks' => 55, 'total' => 100, 'expected' => 'C+'],
            ['marks' => 45, 'total' => 100, 'expected' => 'C'],
            ['marks' => 35, 'total' => 100, 'expected' => 'D'],
            ['marks' => 25, 'total' => 100, 'expected' => 'F'],
        ];

        foreach ($testCases as $case) {
            $grade = $this->examService->calculateGrade($case['marks'], $case['total']);
            $this->assertEquals(
                $case['expected'],
                $grade,
                "Grade for {$case['marks']}/{$case['total']} should be {$case['expected']}"
            );
        }
    }

    /** @test */
    public function it_calculates_percentage_correctly()
    {
        $testCases = [
            ['marks' => 85, 'total' => 100, 'expected' => 85.0],
            ['marks' => 42, 'total' => 50, 'expected' => 84.0],
            ['marks' => 0, 'total' => 100, 'expected' => 0.0],
            ['marks' => 100, 'total' => 100, 'expected' => 100.0],
        ];

        foreach ($testCases as $case) {
            $percentage = $this->examService->calculatePercentage($case['marks'], $case['total']);
            $this->assertEquals(
                $case['expected'],
                $percentage,
                "Percentage for {$case['marks']}/{$case['total']} should be {$case['expected']}%"
            );
        }
    }

    /** @test */
    public function it_prevents_duplicate_result_entry()
    {
        // Arrange - Create existing result
        ExamResult::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 75,
            'total_marks' => 100,
            'grade' => 'B+'
        ]);

        $duplicateResultData = [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 85,
            'total_marks' => 100
        ];

        // Act
        $result = $this->examService->recordExamResult($duplicateResultData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
        
        // Verify original result is unchanged
        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'marks_obtained' => 75
        ]);
    }

    /** @test */
    public function it_updates_exam_result_successfully()
    {
        // Arrange
        $examResult = ExamResult::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 75,
            'total_marks' => 100,
            'grade' => 'B+'
        ]);

        $updateData = [
            'marks_obtained' => 85,
            'grade' => 'A',
            'remarks' => 'Improved performance'
        ];

        // Act
        $result = $this->examService->updateExamResult($examResult->id, $updateData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('exam_results', [
            'id' => $examResult->id,
            'marks_obtained' => 85,
            'grade' => 'A'
        ]);
    }

    /** @test */
    public function it_validates_marks_within_total_marks()
    {
        // Test valid marks
        $validResult = $this->examService->validateMarks(85, 100);
        $this->assertTrue($validResult['valid']);

        // Test marks exceeding total
        $invalidResult = $this->examService->validateMarks(105, 100);
        $this->assertFalse($invalidResult['valid']);
        $this->assertStringContainsString('exceed', $invalidResult['message']);

        // Test negative marks
        $negativeResult = $this->examService->validateMarks(-5, 100);
        $this->assertFalse($negativeResult['valid']);
        $this->assertStringContainsString('negative', $negativeResult['message']);
    }

    /** @test */
    public function it_generates_student_report_card()
    {
        // Arrange - Create multiple exam results for the student
        $subjects = Subject::factory()->count(3)->create();
        $exams = Exam::factory()->count(2)->create([
            'class_id' => $this->class->id
        ]);

        foreach ($exams as $exam) {
            foreach ($subjects as $index => $subject) {
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'student_id' => $this->student->id,
                    'subject_id' => $subject->id,
                    'marks_obtained' => 80 + ($index * 5), // 80, 85, 90
                    'total_marks' => 100,
                    'grade' => $this->examService->calculateGrade(80 + ($index * 5), 100)
                ]);
            }
        }

        // Act
        $reportCard = $this->examService->generateReportCard($this->student->id);

        // Assert
        $this->assertArrayHasKey('student', $reportCard);
        $this->assertArrayHasKey('results', $reportCard);
        $this->assertArrayHasKey('overall_percentage', $reportCard);
        $this->assertArrayHasKey('overall_grade', $reportCard);
        
        $this->assertEquals($this->student->id, $reportCard['student']['id']);
        $this->assertCount(6, $reportCard['results']); // 2 exams × 3 subjects
    }

    /** @test */
    public function it_calculates_class_average_correctly()
    {
        // Arrange
        $students = Student::factory()->count(5)->create([
            'class_id' => $this->class->id
        ]);

        $marks = [85, 90, 75, 80, 70]; // Average should be 80

        foreach ($students as $index => $student) {
            ExamResult::create([
                'exam_id' => $this->exam->id,
                'student_id' => $student->id,
                'subject_id' => $this->subject->id,
                'marks_obtained' => $marks[$index],
                'total_marks' => 100
            ]);
        }

        // Act
        $average = $this->examService->calculateClassAverage($this->exam->id, $this->subject->id);

        // Assert
        $this->assertEquals(80.0, $average);
    }

    /** @test */
    public function it_identifies_top_performers()
    {
        // Arrange
        $students = Student::factory()->count(5)->create([
            'class_id' => $this->class->id
        ]);

        $marks = [95, 88, 92, 85, 90]; // Top 3 should be: 95, 92, 90

        foreach ($students as $index => $student) {
            ExamResult::create([
                'exam_id' => $this->exam->id,
                'student_id' => $student->id,
                'subject_id' => $this->subject->id,
                'marks_obtained' => $marks[$index],
                'total_marks' => 100
            ]);
        }

        // Act
        $topPerformers = $this->examService->getTopPerformers($this->exam->id, $this->subject->id, 3);

        // Assert
        $this->assertCount(3, $topPerformers);
        $this->assertEquals(95, $topPerformers[0]['marks_obtained']);
        $this->assertEquals(92, $topPerformers[1]['marks_obtained']);
        $this->assertEquals(90, $topPerformers[2]['marks_obtained']);
    }

    /** @test */
    public function it_generates_exam_statistics()
    {
        // Arrange
        $students = Student::factory()->count(10)->create([
            'class_id' => $this->class->id
        ]);

        $marks = [95, 85, 75, 65, 55, 45, 35, 85, 75, 65]; // Mix of pass/fail

        foreach ($students as $index => $student) {
            ExamResult::create([
                'exam_id' => $this->exam->id,
                'student_id' => $student->id,
                'subject_id' => $this->subject->id,
                'marks_obtained' => $marks[$index],
                'total_marks' => 100
            ]);
        }

        // Act
        $statistics = $this->examService->generateExamStatistics($this->exam->id, $this->subject->id);

        // Assert
        $this->assertEquals(10, $statistics['total_students']);
        $this->assertEquals(7, $statistics['passed']); // >= 40 marks
        $this->assertEquals(3, $statistics['failed']); // < 40 marks
        $this->assertEquals(70.0, $statistics['pass_percentage']);
        $this->assertEquals(67.0, $statistics['average_marks']);
        $this->assertEquals(95, $statistics['highest_marks']);
        $this->assertEquals(35, $statistics['lowest_marks']);
    }

    /** @test */
    public function it_handles_bulk_result_import()
    {
        // Arrange
        $students = Student::factory()->count(3)->create([
            'class_id' => $this->class->id
        ]);

        $bulkResults = [];
        foreach ($students as $index => $student) {
            $bulkResults[] = [
                'exam_id' => $this->exam->id,
                'student_id' => $student->id,
                'subject_id' => $this->subject->id,
                'marks_obtained' => 80 + ($index * 5),
                'total_marks' => 100
            ];
        }

        // Act
        $result = $this->examService->importBulkResults($bulkResults);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        // Verify all results are created
        foreach ($students as $student) {
            $this->assertDatabaseHas('exam_results', [
                'exam_id' => $this->exam->id,
                'student_id' => $student->id
            ]);
        }
    }

    /** @test */
    public function it_validates_exam_schedule_conflicts()
    {
        // Arrange
        $conflictingExam = [
            'name' => 'Conflicting Exam',
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'exam_date' => $this->exam->exam_date->format('Y-m-d'), // Same date as existing exam
            'start_time' => '10:00:00',
            'end_time' => '12:00:00'
        ];

        // Act
        $result = $this->examService->checkScheduleConflict($conflictingExam);

        // Assert
        $this->assertTrue($result['has_conflict']);
        $this->assertStringContainsString('conflict', $result['message']);
    }

    /** @test */
    public function it_calculates_subject_wise_performance()
    {
        // Arrange
        $subjects = Subject::factory()->count(3)->create();
        
        foreach ($subjects as $index => $subject) {
            ExamResult::create([
                'exam_id' => $this->exam->id,
                'student_id' => $this->student->id,
                'subject_id' => $subject->id,
                'marks_obtained' => 70 + ($index * 10), // 70, 80, 90
                'total_marks' => 100
            ]);
        }

        // Act
        $performance = $this->examService->getSubjectWisePerformance($this->student->id, $this->exam->id);

        // Assert
        $this->assertCount(3, $performance);
        
        foreach ($performance as $index => $subjectPerformance) {
            $expectedMarks = 70 + ($index * 10);
            $this->assertEquals($expectedMarks, $subjectPerformance['marks_obtained']);
            $this->assertEquals($expectedMarks, $subjectPerformance['percentage']);
        }
    }
}