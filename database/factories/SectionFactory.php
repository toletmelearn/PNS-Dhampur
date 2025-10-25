<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => ClassModel::factory(),
            'name' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'teacher_id' => null,
            'capacity' => fake()->numberBetween(25, 40),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the section has a teacher assigned.
     */
    public function withTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
        ]);
    }

    /**
     * Indicate that the section is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific section name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Set for a specific class.
     */
    public function forClass(ClassModel $class): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $class->id,
        ]);
    }
}