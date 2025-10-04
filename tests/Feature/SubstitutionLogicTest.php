<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAvailability;
use App\Models\TeacherAbsence;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\BellTiming;
use App\Services\FreePeriodDetectionService;
use Carbon\Carbon;

class SubstitutionLogicTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $teacher1;
    protected $teacher2;
    protected $teacher3;
    protected $subject;
    protected $class;
    protected $freePeriodService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create teachers
        $this->teacher1 = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 5
        ]);

        $this->teacher2 = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
            'is_active' => true,
            'can_substitute' => true,
            'experience_years' => 3
        ]);

        $this->teacher3 = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
            'is_active' => true,
            'can_substitute' => false,
            'experience_years' => 2
        ]);

        // Create subject and class
        $this->subject = Subject::factory()->create(['name' => 'Mathematics']);
        $this->class = ClassModel::factory()->create(['name' => 'Class 10A']);

        // Associate teachers with subjects
        $this->teacher1->subjects()->attach($this->subject->id);
        $this->teacher2->subjects()->attach($this->subject->id);

        // Initialize service
        $this->freePeriodService = new FreePeriodDetectionService();
    }

    /** @test */
    public function it_can_find_free_teachers_for_substitution()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability for teachers
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        TeacherAvailability::create([
            'teacher_id' => $this->teacher2->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 2
        ]);

        $freeTeachers = $this->freePeriodService->findFreeTeachers($date, $startTime, $endTime);

        $this->assertCount(2, $freeTeachers);
        $this->assertTrue($freeTeachers->contains('id', $this->teacher1->id));
        $this->assertTrue($freeTeachers->contains('id', $this->teacher2->id));
    }

    /** @test */
    public function it_excludes_teachers_on_leave()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability for teacher1
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Put teacher1 on leave
        TeacherAbsence::create([
            'teacher_id' => $this->teacher1->id,
            'absence_date' => $date,
            'end_date' => $date,
            'reason' => 'sick_leave',
            'status' => 'approved'
        ]);

        $freeTeachers = $this->freePeriodService->findFreeTeachers($date, $startTime, $endTime);

        $this->assertFalse($freeTeachers->contains('id', $this->teacher1->id));
    }

    /** @test */
    public function it_excludes_teachers_with_conflicting_substitutions()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability for teacher1
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Create conflicting substitution
        TeacherSubstitution::create([
            'absent_teacher_id' => $this->teacher2->id,
            'substitute_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'start_time' => '09:30',
            'end_time' => '10:30',
            'status' => 'confirmed',
            'priority' => 'normal',
            'is_emergency' => false,
            'requested_by' => $this->admin->id,
            'requested_at' => now()
        ]);

        $freeTeachers = $this->freePeriodService->findFreeTeachers($date, $startTime, $endTime);

        $this->assertFalse($freeTeachers->contains('id', $this->teacher1->id));
    }

    /** @test */
    public function it_respects_daily_substitution_limits()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability with limit of 1 substitution
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 1
        ]);

        // Create existing substitution for today
        TeacherSubstitution::create([
            'absent_teacher_id' => $this->teacher2->id,
            'substitute_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'completed',
            'priority' => 'normal',
            'is_emergency' => false,
            'requested_by' => $this->admin->id,
            'requested_at' => now()
        ]);

        $freeTeachers = $this->freePeriodService->findFreeTeachers($date, $startTime, $endTime);

        $this->assertFalse($freeTeachers->contains('id', $this->teacher1->id));
    }

    /** @test */
    public function it_finds_best_substitute_based_on_subject_expertise()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability for both teachers
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        TeacherAvailability::create([
            'teacher_id' => $this->teacher2->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Teacher1 has more experience and subject expertise
        $bestSubstitute = $this->freePeriodService->findBestSubstitute(
            $date, 
            $startTime, 
            $endTime, 
            $this->subject->id, 
            $this->class->id
        );

        $this->assertNotNull($bestSubstitute);
        $this->assertEquals($this->teacher1->id, $bestSubstitute->id);
    }

    /** @test */
    public function it_can_create_substitution_request_via_api()
    {
        $this->actingAs($this->admin);

        $date = Carbon::tomorrow()->format('Y-m-d');
        
        $response = $this->postJson('/api/substitutions', [
            'absent_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'subject_id' => $this->subject->id,
            'reason' => 'sick_leave',
            'priority' => 'normal',
            'is_emergency' => false
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teacher_substitutions', [
            'absent_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_assign_substitute_teacher()
    {
        $this->actingAs($this->admin);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // Create availability for substitute teacher
        TeacherAvailability::create([
            'teacher_id' => $this->teacher2->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        // Create substitution request
        $substitution = TeacherSubstitution::create([
            'absent_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
            'priority' => 'normal',
            'is_emergency' => false,
            'requested_by' => $this->admin->id,
            'requested_at' => now()
        ]);

        $response = $this->putJson("/api/substitutions/{$substitution->id}/assign", [
            'substitute_teacher_id' => $this->teacher2->id
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('teacher_substitutions', [
            'id' => $substitution->id,
            'substitute_teacher_id' => $this->teacher2->id,
            'status' => 'assigned'
        ]);
    }

    /** @test */
    public function it_validates_substitution_request_data()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/substitutions', [
            'absent_teacher_id' => 999, // Non-existent teacher
            'class_id' => $this->class->id,
            'substitution_date' => 'invalid-date',
            'start_time' => '25:00', // Invalid time
            'end_time' => '08:00', // End before start
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'absent_teacher_id',
            'substitution_date',
            'start_time',
            'end_time'
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_substitution_endpoints()
    {
        // Test without authentication
        $response = $this->getJson('/api/substitutions');
        $response->assertStatus(401);

        // Test with student role (assuming students can't manage substitutions)
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student);

        $response = $this->postJson('/api/substitutions', [
            'absent_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_real_time_free_teachers()
    {
        $date = Carbon::today()->format('Y-m-d');

        // Create bell timing for current period
        BellTiming::create([
            'period_name' => 'Period 1',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_active' => true,
            'season' => 'winter'
        ]);

        // Create availability
        TeacherAvailability::create([
            'teacher_id' => $this->teacher1->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3
        ]);

        $realTimeFreeTeachers = $this->freePeriodService->getRealTimeFreeTeachers($date);

        $this->assertIsArray($realTimeFreeTeachers);
        $this->assertArrayHasKey('free_teachers', $realTimeFreeTeachers);
    }

    /** @test */
    public function it_handles_emergency_substitution_requests()
    {
        $this->actingAs($this->admin);

        $date = Carbon::today()->format('Y-m-d');
        
        $response = $this->postJson('/api/substitutions', [
            'absent_teacher_id' => $this->teacher1->id,
            'class_id' => $this->class->id,
            'substitution_date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'reason' => 'emergency',
            'priority' => 'high',
            'is_emergency' => true
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teacher_substitutions', [
            'absent_teacher_id' => $this->teacher1->id,
            'is_emergency' => true,
            'priority' => 'high'
        ]);
    }

    /** @test */
    public function it_excludes_teachers_who_cannot_substitute()
    {
        $date = Carbon::today()->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // Create availability for teacher3 who cannot substitute
        TeacherAvailability::create([
            'teacher_id' => $this->teacher3->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'available',
            'can_substitute' => false,
            'max_substitutions_per_day' => 3
        ]);

        $freeTeachers = $this->freePeriodService->findFreeTeachers($date, $startTime, $endTime);

        $this->assertFalse($freeTeachers->contains('id', $this->teacher3->id));
    }
}
