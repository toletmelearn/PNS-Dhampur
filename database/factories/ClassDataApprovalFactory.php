<?php

namespace Database\Factories;

use App\Models\ClassDataApproval;
use App\Models\ClassDataAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassDataApproval>
 */
class ClassDataApprovalFactory extends Factory
{
    protected $model = ClassDataApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => ClassDataAudit::factory(),
            'approver_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'delegated', 'escalated', 'expired', 'cancelled']),
            'approval_type' => fake()->randomElement(['data_change', 'bulk_operation', 'critical_update', 'deletion', 'restoration', 'import', 'export', 'merge', 'split']),
            'level' => fake()->numberBetween(1, 3),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent', 'critical']),
            'comments' => fake()->sentence(),
            'approved_at' => null,
            'expires_at' => fake()->dateTimeBetween('now', '+30 days'),
            'metadata' => json_encode(['created_at' => now()->toISOString()]),
        ];
    }

    /**
     * Indicate that the approval is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
            'rejection_reason' => null,
            'delegated_to' => null,
            'escalated_to' => null
        ]);
    }

    /**
     * Indicate that the approval is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'responded_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
            'comments' => $this->faker->optional()->sentence(),
            'rejection_reason' => null
        ]);
    }

    /**
     * Indicate that the approval is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'responded_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
            'rejection_reason' => $this->faker->sentence(),
            'comments' => $this->faker->optional()->sentence()
        ]);
    }

    /**
     * Indicate that the approval is delegated.
     */
    public function delegated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delegated',
            'delegated_to' => User::factory(),
            'delegated_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
            'delegation_reason' => $this->faker->sentence()
        ]);
    }

    /**
     * Indicate that the approval is escalated.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'escalated',
            'escalated_to' => User::factory(),
            'escalated_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
            'escalation_reason' => $this->faker->sentence(),
            'level' => $attributes['level'] + 1
        ]);
    }

    /**
     * Indicate that the approval is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'expires_at' => $this->faker->dateTimeBetween('now', '+2 days'), // Shorter expiry
            'reminder_count' => $this->faker->numberBetween(1, 5)
        ]);
    }

    /**
     * Indicate that the approval is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 day'), // Very short expiry
            'reminder_count' => $this->faker->numberBetween(2, 8),
            'notification_sent' => true
        ]);
    }

    /**
     * Indicate that the approval is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            'reminder_count' => $this->faker->numberBetween(3, 10),
            'last_reminder_at' => $this->faker->dateTimeBetween('-3 days', '-1 day')
        ]);
    }

    /**
     * Indicate that this is a multi-level approval.
     */
    public function multiLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_type' => 'multi_level',
            'level' => $this->faker->numberBetween(1, 3),
            'approval_criteria' => array_merge($attributes['approval_criteria'] ?? [], [
                'requires_all_levels' => true,
                'sequential_approval' => $this->faker->boolean()
            ])
        ]);
    }
}