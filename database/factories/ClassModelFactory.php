<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassModel>
 */
class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10']),
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'class_teacher_id' => null, // Will be set if needed
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(20, 50),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the class has a teacher assigned.
     */
    public function withTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'class_teacher_id' => Teacher::factory(),
        ]);
    }

    /**
     * Indicate that the class is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific class name and section.
     */
    public function nameAndSection(string $name, string $section): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'section' => $section,
        ]);
    }
}