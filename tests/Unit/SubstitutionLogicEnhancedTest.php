<?php

namespace Tests\Unit;

// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Tests\TestCase;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\TeacherAvailability;
use App\Models\TeacherSubstitution;
use App\Services\FreePeriodDetectionService;
use Carbon\Carbon;

class SubstitutionLogicEnhancedTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    /** @test */
    public function it_auto_assigns_best_matching_substitute()
    {
        $service = new FreePeriodDetectionService();

        $date = Carbon::today()->format('Y-m-d');
        $startTime = '10:00';
        $endTime = '11:00';

        $subject = Subject::factory()->create(['name' => 'Science']);
        $class = ClassModel::factory()->create(['name' => 'Class 9B']);

        $expertTeacher = Teacher::factory()->create([
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 8
        ]);

        $familiarTeacher = Teacher::factory()->create([
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 4
        ]);

        TeacherAvailability::create([
            'teacher_id' => $expertTeacher->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        TeacherAvailability::create([
            'teacher_id' => $familiarTeacher->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Expert teacher teaches the subject
        Subject::where('id', $subject->id)->update(['teacher_id' => $expertTeacher->id]);
        // Familiar teacher is the class teacher
        ClassModel::where('id', $class->id)->update(['class_teacher_id' => $familiarTeacher->id]);

        $best = $service->findBestSubstitute(
            $date,
            $startTime,
            $endTime,
            $subject->id,
            $class->id
        );

        $this->assertNotNull($best);
        $this->assertEquals($expertTeacher->id, $best->id);
    }

    /** @test */
    public function enhanced_free_period_service_finds_best_substitute()
    {
        $service = new FreePeriodDetectionService();

        $date = Carbon::today()->format('Y-m-d');
        $startTime = '10:00';
        $endTime = '11:00';

        $subject = Subject::factory()->create(['name' => 'Mathematics']);
        $relatedSubject = Subject::factory()->create(['name' => 'Algebra', 'class_id' => ClassModel::factory()->create()->id]);
        $class = ClassModel::factory()->create(['name' => 'Class 10B']);

        $expertTeacher = Teacher::factory()->create([
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 10
        ]);

        $relatedTeacher = Teacher::factory()->create([
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 6
        ]);

        TeacherAvailability::create([
            'teacher_id' => $expertTeacher->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        TeacherAvailability::create([
            'teacher_id' => $relatedTeacher->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Set expertise and related subject
        Subject::where('id', $subject->id)->update(['teacher_id' => $expertTeacher->id]);
        Subject::where('id', $relatedSubject->id)->update(['teacher_id' => $relatedTeacher->id]);

        $best = $service->findBestSubstitute(
            $date,
            $startTime,
            $endTime,
            $subject->id,
            $class->id
        );

        $this->assertNotNull($best);
        $this->assertEquals($expertTeacher->id, $best->id);
    }
}