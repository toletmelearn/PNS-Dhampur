<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditTrail>
 */
class AuditTrailFactory extends Factory
{
    protected $model = AuditTrail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'viewed', 'exported', 'correction', 'bulk_update', 'bulk_delete'];
        $auditableTypes = [
            'App\\Models\\Student',
            'App\\Models\\Attendance',
            'App\\Models\\Fee',
            'App\\Models\\StudentVerification',
            'App\\Models\\ClassModel',
            'App\\Models\\User'
        ];
        
        $event = fake()->randomElement($events);
        $auditableType = fake()->randomElement($auditableTypes);
        
        return [
            'user_id' => User::factory(),
            'auditable_type' => $auditableType,
            'auditable_id' => fake()->numberBetween(1, 100),
            'event' => $event,
            'old_values' => $this->generateOldValues(),
            'new_values' => $this->generateNewValues(),
            'url' => fake()->url(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'tags' => fake()->randomElements(['system', 'manual', 'bulk', 'correction', 'approval'], fake()->numberBetween(1, 3)),
            'student_id' => fake()->optional()->randomElement([null, Student::factory()]),
            'class_id' => fake()->optional()->randomElement([null, ClassModel::factory()]),
            'subject_id' => fake()->optional()->randomElement([null, Subject::factory()]),
            'academic_year' => fake()->year(),
            'term' => fake()->optional()->randomElement([null, 'Term 1', 'Term 2', 'Term 3']),
            'correction_reason' => fake()->optional()->sentence(),
            'approved_by' => fake()->optional()->randomElement([null, User::factory()]),
            'approved_at' => fake()->optional()->dateTime(),
            'rejection_reason' => fake()->optional()->sentence(),
            'rejected_by' => fake()->optional()->randomElement([null, User::factory()]),
            'rejected_at' => fake()->optional()->dateTime(),
            'status' => fake()->randomElement(['normal', 'pending_approval', 'approved', 'rejected'])
        ];
    }

    /**
     * Generate sample old values for audit trail
     */
    private function generateOldValues(): array
    {
        return [
            'name' => fake()->name(),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'marks' => fake()->numberBetween(0, 100),
            'attendance_status' => fake()->randomElement(['present', 'absent', 'late']),
            'amount' => fake()->randomFloat(2, 0, 10000)
        ];
    }

    /**
     * Generate sample new values for audit trail
     */
    private function generateNewValues(): array
    {
        return [
            'name' => fake()->name(),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'marks' => fake()->numberBetween(0, 100),
            'attendance_status' => fake()->randomElement(['present', 'absent', 'late']),
            'amount' => fake()->randomFloat(2, 0, 10000)
        ];
    }

    /**
     * Indicate that the audit trail is for a correction event.
     */
    public function correction(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'correction',
            'status' => 'pending_approval',
            'correction_reason' => fake()->sentence()
        ]);
    }

    /**
     * Indicate that the audit trail is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTime()
        ]);
    }

    /**
     * Indicate that the audit trail is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_by' => User::factory(),
            'rejected_at' => fake()->dateTime(),
            'rejection_reason' => fake()->sentence()
        ]);
    }

    /**
     * Indicate that the audit trail is for a bulk operation.
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => fake()->randomElement(['bulk_update', 'bulk_delete', 'bulk_create']),
            'tags' => ['bulk', 'system']
        ]);
    }

    /**
     * Indicate that the audit trail is for a student.
     */
    public function forStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => Student::factory(),
            'auditable_type' => 'App\\Models\\Student'
        ]);
    }

    /**
     * Indicate that the audit trail is for a class.
     */
    public function forClass(): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => ClassModel::factory(),
            'auditable_type' => 'App\\Models\\ClassModel'
        ]);
    }

    /**
     * Indicate that the audit trail is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-7 days', 'now')
        ]);
    }
}