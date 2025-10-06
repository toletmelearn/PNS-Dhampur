<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAbsence;
use App\Services\FreePeriodDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SubstitutionLogicEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected $freePeriodService;
    protected $testDate;
    protected $testStartTime;
    protected $testEndTime;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->freePeriodService = app(FreePeriodDetectionService::class);
        $this->testDate = Carbon::tomorrow()->format('Y-m-d');
        $this->testStartTime = '09:00:00';
        $this->testEndTime = '10:00:00';
    }

    /** @test */
    public function it_finds_available_substitutes_with_subject_compatibility()
    {
        // Create test data
        $subject = Subject::factory()->create(['name' => 'Mathematics']);
        $class = ClassModel::factory()->create(['name' => 'Class 10A']);
        
        $teacher1 = Teacher::factory()->create(['name' => 'Math Expert']);
        $teacher2 = Teacher::factory()->create(['name' => 'Science Teacher']);
        $teacher3 = Teacher::factory()->create(['name' => 'General Teacher']);
        
        // Assign subject expertise
        $teacher1->subjects()->attach($subject->id);
        
        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime,
            $subject->id,
            $class->id
        );

        $this->assertGreaterThan(0, $availableTeachers->count());
        $this->assertTrue($availableTeachers->contains('id', $teacher1->id));
    }

    /** @test */
    public function it_excludes_absent_teachers_from_substitution_pool()
    {
        $teacher = Teacher::factory()->create();
        
        // Create absence for the teacher
        TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'absence_date' => $this->testDate,
            'status' => 'approved'
        ]);

        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime
        );

        $this->assertFalse($availableTeachers->contains('id', $teacher->id));
    }

    /** @test */
    public function it_excludes_teachers_with_conflicting_substitutions()
    {
        $teacher = Teacher::factory()->create();
        
        // Create conflicting substitution
        TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'substitution_date' => $this->testDate,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'status' => TeacherSubstitution::STATUS_CONFIRMED
        ]);

        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime
        );

        $this->assertFalse($availableTeachers->contains('id', $teacher->id));
    }

    /** @test */
    public function it_respects_daily_substitution_limits()
    {
        $teacher = Teacher::factory()->create();
        
        // Create 3 substitutions for the same day (max limit)
        for ($i = 0; $i < 3; $i++) {
            TeacherSubstitution::factory()->create([
                'substitute_teacher_id' => $teacher->id,
                'substitution_date' => $this->testDate,
                'start_time' => sprintf('%02d:00:00', 8 + $i),
                'end_time' => sprintf('%02d:00:00', 9 + $i),
                'status' => TeacherSubstitution::STATUS_CONFIRMED
            ]);
        }

        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $this->testDate,
            '12:00:00',
            '13:00:00'
        );

        $this->assertFalse($availableTeachers->contains('id', $teacher->id));
    }

    /** @test */
    public function it_auto_assigns_best_matching_substitute()
    {
        $subject = Subject::factory()->create(['name' => 'Physics']);
        $class = ClassModel::factory()->create(['name' => 'Class 11B']);
        
        $expertTeacher = Teacher::factory()->create(['name' => 'Physics Expert']);
        $generalTeacher = Teacher::factory()->create(['name' => 'General Teacher']);
        
        // Give subject expertise to expert teacher
        $expertTeacher->subjects()->attach($subject->id);
        $expertTeacher->classes()->attach($class->id);
        
        $substitution = TeacherSubstitution::factory()->create([
            'substitution_date' => $this->testDate,
            'start_time' => $this->testStartTime,
            'end_time' => $this->testEndTime,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'status' => TeacherSubstitution::STATUS_PENDING
        ]);

        $assignedTeacher = TeacherSubstitution::autoAssignSubstitute($substitution->id);

        $this->assertNotNull($assignedTeacher);
        $this->assertEquals($expertTeacher->id, $assignedTeacher->id);
        
        $substitution->refresh();
        $this->assertEquals($expertTeacher->id, $substitution->substitute_teacher_id);
        $this->assertTrue($substitution->auto_assigned);
    }

    /** @test */
    public function it_calculates_teacher_performance_metrics()
    {
        $teacher = Teacher::factory()->create();
        
        // Create various substitution records
        TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'status' => TeacherSubstitution::STATUS_COMPLETED,
            'rating' => 5,
            'substitution_date' => Carbon::now()->subDays(10)
        ]);
        
        TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'status' => TeacherSubstitution::STATUS_DECLINED,
            'substitution_date' => Carbon::now()->subDays(5)
        ]);

        $performance = TeacherSubstitution::getTeacherPerformance($teacher->id, 1);

        $this->assertEquals(2, $performance['total_substitutions']);
        $this->assertEquals(1, $performance['completed_substitutions']);
        $this->assertEquals(1, $performance['declined_substitutions']);
        $this->assertEquals(50, $performance['reliability_score']);
        $this->assertEquals(5, $performance['average_rating']);
    }

    /** @test */
    public function it_detects_substitution_conflicts()
    {
        $teacher = Teacher::factory()->create();
        
        $substitution1 = TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'substitution_date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => TeacherSubstitution::STATUS_CONFIRMED
        ]);

        $substitution2 = TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'substitution_date' => $this->testDate,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
            'status' => TeacherSubstitution::STATUS_PENDING
        ]);

        $hasConflict = $substitution2->hasConflict(
            $this->testDate,
            '09:30:00',
            '10:30:00'
        );

        $this->assertTrue($hasConflict);
    }

    /** @test */
    public function it_generates_comprehensive_substitution_stats()
    {
        $teacher = Teacher::factory()->create();
        
        // Create various substitution records
        TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'status' => TeacherSubstitution::STATUS_COMPLETED,
            'rating' => 4,
            'is_emergency' => true
        ]);
        
        TeacherSubstitution::factory()->create([
            'substitute_teacher_id' => $teacher->id,
            'status' => TeacherSubstitution::STATUS_PENDING,
            'rating' => 5
        ]);

        $stats = TeacherSubstitution::getSubstitutionStats($teacher->id);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['emergency_count']);
        $this->assertEquals(4.5, $stats['average_rating']);
    }

    /** @test */
    public function enhanced_free_period_service_finds_best_substitute()
    {
        $subject = Subject::factory()->create(['name' => 'Chemistry']);
        $class = ClassModel::factory()->create(['name' => 'Class 12A']);
        
        $expertTeacher = Teacher::factory()->create(['name' => 'Chemistry Expert']);
        $relatedTeacher = Teacher::factory()->create(['name' => 'Biology Teacher']);
        $generalTeacher = Teacher::factory()->create(['name' => 'General Teacher']);
        
        // Set up subject relationships
        $expertTeacher->subjects()->attach($subject->id);
        $relatedSubject = Subject::factory()->create(['name' => 'Biology']);
        $relatedTeacher->subjects()->attach($relatedSubject->id);
        
        $options = [
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'priority_level' => 'high'
        ];

        $result = $this->freePeriodService->findBestSubstituteEnhanced(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime,
            $options
        );

        $this->assertNotNull($result['primary_substitute']);
        $this->assertArrayHasKey('matching_strategy', $result);
        $this->assertArrayHasKey('confidence_score', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['backup_substitutes']);
        $this->assertIsArray($result['emergency_options']);
    }

    /** @test */
    public function it_handles_emergency_substitution_scenarios()
    {
        // Create scenario with no available teachers
        $allTeachers = Teacher::factory()->count(3)->create();
        
        // Make all teachers unavailable
        foreach ($allTeachers as $teacher) {
            TeacherAbsence::factory()->create([
                'teacher_id' => $teacher->id,
                'absence_date' => $this->testDate,
                'status' => 'approved'
            ]);
        }

        $options = [
            'priority_level' => 'emergency',
            'allow_emergency_protocols' => true
        ];

        $result = $this->freePeriodService->findBestSubstituteEnhanced(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime,
            $options
        );

        $this->assertNull($result['primary_substitute']);
        $this->assertEquals('emergency', $result['matching_strategy']);
        $this->assertArrayHasKey('emergency_options', $result);
    }

    /** @test */
    public function it_prioritizes_teachers_with_class_familiarity()
    {
        $subject = Subject::factory()->create(['name' => 'English']);
        $class = ClassModel::factory()->create(['name' => 'Class 9C']);
        
        $familiarTeacher = Teacher::factory()->create(['name' => 'Class Familiar Teacher']);
        $unfamiliarTeacher = Teacher::factory()->create(['name' => 'Unfamiliar Teacher']);
        
        // Both teachers can teach the subject
        $familiarTeacher->subjects()->attach($subject->id);
        $unfamiliarTeacher->subjects()->attach($subject->id);
        
        // Only one teacher is familiar with the class
        $familiarTeacher->classes()->attach($class->id);

        $options = [
            'subject_id' => $subject->id,
            'class_id' => $class->id
        ];

        $result = $this->freePeriodService->findBestSubstituteEnhanced(
            $this->testDate,
            $this->testStartTime,
            $this->testEndTime,
            $options
        );

        $this->assertNotNull($result['primary_substitute']);
        $this->assertEquals($familiarTeacher->id, $result['primary_substitute']->id);
        $this->assertGreaterThan(0.7, $result['confidence_score']); // High confidence due to familiarity
    }
}