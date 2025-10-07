<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'admission_no' => 'ADM' . fake()->unique()->numberBetween(1000, 9999),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'dob' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'aadhaar' => fake()->numerify('############'),
            'class_id' => ClassModel::factory(),
            'verification_status' => fake()->randomElement(['pending', 'verified', 'rejected']),
            'status' => fake()->randomElement(['active', 'inactive', 'transferred']),
            'documents' => null,
            'documents_verified_data' => null,
            'verified' => fake()->boolean(),
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the student is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the student is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
        ]);
    }

    /**
     * Indicate that the student is in a specific class.
     */
    public function inClass(ClassModel $class): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $class->id,
        ]);
    }

    /**
     * Set specific admission number.
     */
    public function withAdmissionNumber(string $admissionNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'admission_no' => $admissionNumber,
        ]);
    }
}