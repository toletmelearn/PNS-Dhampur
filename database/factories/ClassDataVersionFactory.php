<?php

namespace Database\Factories;

use App\Models\ClassDataVersion;
use App\Models\ClassDataAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassDataVersion>
 */
class ClassDataVersionFactory extends Factory
{
    protected $model = ClassDataVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $versionCounter = 1;
        
        return [
            'audit_id' => \App\Models\ClassDataAudit::factory(),
            'version_number' => $versionCounter++,
            'data_snapshot' => json_encode(['field1' => 'value1', 'field2' => 'value2']),
            'changes_summary' => fake()->sentence(),
            'created_by' => \App\Models\User::factory(),
            'version_type' => fake()->randomElement(['automatic', 'manual', 'scheduled', 'rollback', 'merge']),
            'is_current_version' => fake()->boolean(),
            'checksum' => hash('sha256', fake()->text()),
            'size_bytes' => fake()->numberBetween(1024, 1048576),
            'compression_type' => fake()->randomElement(['none', 'gzip', 'bzip2']),
            'metadata' => json_encode(['created_at' => now()->toISOString()]),
            'tags' => json_encode([fake()->word(), fake()->word()]),
        ];
    }

    /**
     * Indicate that this is the current version.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current_version' => true,
            'version_type' => ClassDataVersion::TYPE_MANUAL
        ]);
    }

    /**
     * Indicate that this is an initial version.
     */
    public function initial(): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => 1,
            'version_type' => ClassDataVersion::TYPE_AUTOMATIC,
            'parent_version_id' => null,
            'is_current_version' => true
        ]);
    }

    /**
     * Indicate that this is a rollback version.
     */
    public function rollback(): static
    {
        return $this->state(fn (array $attributes) => [
            'version_type' => ClassDataVersion::TYPE_ROLLBACK,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'rollback_reason' => $this->faker->sentence(),
                'rollback_target_version' => $this->faker->numberBetween(1, 10)
            ])
        ]);
    }

    /**
     * Indicate that this is a merge version.
     */
    public function merge(): static
    {
        return $this->state(fn (array $attributes) => [
            'version_type' => ClassDataVersion::TYPE_MERGE,
            'merge_source_versions' => [
                $this->faker->numberBetween(1, 10),
                $this->faker->numberBetween(11, 20)
            ],
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'merge_strategy' => $this->faker->randomElement(['auto', 'manual', 'conflict_resolution']),
                'conflicts_resolved' => $this->faker->numberBetween(0, 5)
            ])
        ]);
    }

    /**
     * Indicate that this version has a parent.
     */
    public function withParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_version_id' => ClassDataVersion::factory(),
            'version_number' => $this->faker->numberBetween(2, 100)
        ]);
    }

    /**
     * Indicate that this version is compressed.
     */
    public function compressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'compression_type' => ClassDataVersion::COMPRESSION_GZIP,
            'size_bytes' => $this->faker->numberBetween(100, 1000) // Smaller due to compression
        ]);
    }

    /**
     * Indicate that this version is large.
     */
    public function large(): static
    {
        $largeDataSnapshot = array_fill(0, 100, [
            'field' => $this->faker->word(),
            'value' => $this->faker->sentence(),
            'metadata' => $this->faker->words(10)
        ]);

        return $this->state(fn (array $attributes) => [
            'data_snapshot' => $largeDataSnapshot,
            'size_bytes' => strlen(json_encode($largeDataSnapshot)),
            'compression_type' => ClassDataVersion::COMPRESSION_GZIP
        ]);
    }
}