<?php

namespace Database\Factories;

use App\Models\ClassDataAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassDataAudit>
 */
class ClassDataAuditFactory extends Factory
{
    protected $model = ClassDataAudit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auditable_type' => fake()->randomElement(['App\\Models\\Student', 'App\\Models\\Teacher', 'App\\Models\\ClassModel']),
            'auditable_id' => fake()->numberBetween(1, 100),
            'event_type' => fake()->randomElement(['created', 'updated', 'deleted', 'restored', 'bulk_update', 'bulk_delete', 'import', 'export', 'merge', 'split']),
            'old_values' => json_encode(['field1' => 'old_value1', 'field2' => 'old_value2']),
            'new_values' => json_encode(['field1' => 'new_value1', 'field2' => 'new_value2']),
            'changed_fields' => json_encode(['field1', 'field2']),
            'user_id' => User::factory(),
            'user_type' => 'App\\Models\\User',
            'user_name' => fake()->name(),
            'user_role' => fake()->randomElement(['admin', 'teacher', 'student']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'session_id' => fake()->uuid(),
            'request_id' => fake()->uuid(),
            'description' => fake()->sentence(),
            'metadata' => json_encode(['key' => 'value', 'timestamp' => now()->toISOString()]),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'requires_approval' => fake()->boolean(),
            'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected', 'auto_approved']),
            'approved_by' => null,
            'approved_at' => fake()->optional()->dateTime(),
            'batch_id' => fake()->optional()->uuid(),
            'parent_audit_id' => null,
            'checksum' => hash('sha256', fake()->text()),
            'tags' => json_encode([fake()->word(), fake()->word()]),
        ];
    }

    /**
     * Indicate that the audit requires approval.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
            'approval_status' => 'pending',
            'risk_level' => $this->faker->randomElement(['high', 'critical'])
        ]);
    }

    /**
     * Indicate that the audit is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
            'approval_status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now()
        ]);
    }

    /**
     * Indicate that the audit is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
            'approval_status' => 'rejected'
        ]);
    }

    /**
     * Indicate that the audit is high risk.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'high',
            'requires_approval' => true
        ]);
    }

    /**
     * Indicate that the audit is part of a batch operation.
     */
    public function batch(): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_id' => Str::uuid()
        ]);
    }
}