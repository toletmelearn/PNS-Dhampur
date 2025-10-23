<?php

namespace Database\Factories;

use App\Models\ClassData;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassDataFactory extends Factory
{
    protected $model = ClassData::class;

    public function definition()
    {
        return [
            'class_name' => $this->faker->words(3, true) . ' ' . $this->faker->randomElement(['10A', '10B', '11A', '11B', '12A', '12B']),
            'subject' => $this->faker->randomElement([
                'Mathematics',
                'Physics',
                'Chemistry',
                'Biology',
                'English',
                'History',
                'Geography',
                'Computer Science'
            ]),
            'data' => [
                'lesson_plan' => $this->faker->paragraph(),
                'homework' => $this->faker->sentence(),
                'resources' => [
                    'textbook' => $this->faker->words(3, true),
                    'online_resources' => $this->faker->url()
                ],
                'assessment' => [
                    'quiz_date' => $this->faker->date(),
                    'assignment_due' => $this->faker->date()
                ]
            ],
            'metadata' => [
                'academic_year' => $this->faker->randomElement(['2023-24', '2024-25', '2025-26']),
                'term' => $this->faker->randomElement(['First', 'Second', 'Third']),
                'class_strength' => $this->faker->numberBetween(20, 40),
                'room_number' => $this->faker->randomElement(['101', '102', '201', '202', '301', '302'])
            ],
            'status' => $this->faker->randomElement(['active', 'inactive', 'archived']),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now')
        ];
    }

    /**
     * Indicate that the class data is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the class data is archived.
     */
    public function archived()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'archived',
            ];
        });
    }

    /**
     * Create class data for a specific subject.
     */
    public function forSubject(string $subject)
    {
        return $this->state(function (array $attributes) use ($subject) {
            return [
                'subject' => $subject,
            ];
        });
    }

    /**
     * Create class data with specific creator.
     */
    public function createdBy(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];
        });
    }
}