<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'Mathematics', 'English', 'Science', 'Social Studies', 'Hindi',
            'Computer Science', 'Physical Education', 'Art', 'Music', 'Biology',
            'Chemistry', 'Physics', 'Geography', 'History'
        ];

        return [
            'name' => $this->faker->randomElement($names),
            'code' => $this->faker->unique()->bothify('SUBJ-####'),
            'description' => $this->faker->optional()->sentence(),
            'class_id' => ClassModel::factory(),
            'teacher_id' => null, // Assigned explicitly in tests or controllers
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the subject is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Assign the subject to a specific class.
     */
    public function forClass(ClassModel $class): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $class->id,
        ]);
    }
}