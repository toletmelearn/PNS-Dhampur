<?php

namespace Database\Factories;

use App\Models\StudentVerification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentVerification>
 */
class StudentVerificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentVerification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'document_type' => $this->faker->randomElement([
                'birth_certificate',
                'aadhar_card', 
                'transfer_certificate',
                'caste_certificate',
                'income_certificate'
            ]),
            'original_file_path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'processed_file_path' => 'processed/' . $this->faker->uuid() . '.pdf',
            'verification_status' => $this->faker->randomElement([
                StudentVerification::STATUS_PENDING,
                StudentVerification::STATUS_VERIFIED,
                StudentVerification::STATUS_REJECTED,
                StudentVerification::STATUS_MANUAL_REVIEW
            ]),
            'verification_method' => $this->faker->randomElement(['ocr', 'manual', 'api']),
            'extracted_data' => [
                'name' => $this->faker->name(),
                'father_name' => $this->faker->name('male'),
                'mother_name' => $this->faker->name('female'),
                'dob' => $this->faker->date(),
                'document_number' => $this->faker->numerify('############')
            ],
            'verification_results' => [
                'name_match' => $this->faker->boolean(80),
                'father_name_match' => $this->faker->boolean(75),
                'dob_match' => $this->faker->boolean(85),
                'document_valid' => $this->faker->boolean(90)
            ],
            'confidence_score' => $this->faker->randomFloat(2, 0, 100),
            'format_valid' => $this->faker->boolean(90),
            'quality_check_passed' => $this->faker->boolean(85),
            'data_consistency_check' => $this->faker->boolean(80),
            'cross_reference_check' => $this->faker->boolean(75),
            'reviewed_by' => null,
            'reviewer_comments' => null,
            'reviewed_at' => null,
            'verification_log' => [
                [
                    'timestamp' => now()->toISOString(),
                    'action' => 'document_uploaded',
                    'details' => 'Document uploaded for verification'
                ]
            ],
            'uploaded_by' => User::factory(),
            'verification_started_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'verification_completed_at' => null,
        ];
    }

    /**
     * Indicate that the verification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => StudentVerification::STATUS_PENDING,
            'verification_completed_at' => null,
        ]);
    }

    /**
     * Indicate that the verification is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'confidence_score' => $this->faker->randomFloat(2, 80, 100),
            'verification_completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the verification is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => StudentVerification::STATUS_REJECTED,
            'confidence_score' => $this->faker->randomFloat(2, 0, 50),
            'verification_completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'reviewer_comments' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the verification needs manual review.
     */
    public function manualReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => $this->faker->randomFloat(2, 40, 70),
            'verification_completed_at' => null,
        ]);
    }

    /**
     * Indicate that the verification has high confidence.
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 85, 100),
            'format_valid' => true,
            'quality_check_passed' => true,
            'data_consistency_check' => true,
            'cross_reference_check' => true,
        ]);
    }

    /**
     * Indicate that the verification has low confidence.
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0, 40),
            'format_valid' => $this->faker->boolean(50),
            'quality_check_passed' => $this->faker->boolean(40),
            'data_consistency_check' => $this->faker->boolean(30),
            'cross_reference_check' => $this->faker->boolean(25),
        ]);
    }
}