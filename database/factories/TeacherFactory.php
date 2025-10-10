<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition()
    {
        return [
            // Ensure each Teacher is linked to a User with an employee_id
            'user_id' => function () {
                return User::factory()->teacher()->create([
                    'employee_id' => fake()->unique()->regexify('EMP[0-9]{3}')
                ])->id;
            },
            'qualification' => $this->faker->randomElement(['B.Ed', 'M.Ed', 'PhD', 'M.Sc', 'M.A']),
            'experience_years' => $this->faker->numberBetween(0, 30),
            'salary' => $this->faker->randomFloat(2, 20000, 120000),
            'joining_date' => $this->faker->date(),
            'documents' => [
                ['type' => 'degree', 'filename' => 'degree.pdf'],
                ['type' => 'resume', 'filename' => 'resume.pdf']
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}