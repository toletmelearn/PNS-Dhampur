<?php

namespace Database\Factories;

use App\Models\Fee;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fee>
 */
class FeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Fee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 1000, 10000);
        $paidAmount = $this->faker->boolean(70) ? $this->faker->randomFloat(2, 0, $amount) : 0;
        
        return [
            'student_id' => Student::factory(),
            'fee_type' => $this->faker->randomElement([
                'tuition',
                'admission',
                'exam',
                'transport',
                'library',
                'lab',
                'sports',
                'other'
            ]),
            'amount' => $amount,
            'due_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'academic_year' => $this->faker->randomElement(['2023-24', '2024-25']),
            'month' => $this->faker->randomElement([
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]),
            'late_fee' => $this->faker->randomFloat(2, 0, 500),
            'discount' => $this->faker->randomFloat(2, 0, 1000),
            'paid_amount' => $paidAmount,
            'paid_date' => $paidAmount > 0 ? $this->faker->dateTimeBetween('-2 months', 'now') : null,
            'status' => $this->determineStatus($amount, $paidAmount),
            'remarks' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Determine the fee status based on amount and paid amount.
     */
    private function determineStatus(float $amount, float $paidAmount): string
    {
        if ($paidAmount >= $amount) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Indicate that the fee is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => 0,
            'paid_date' => null,
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the fee is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'paid_amount' => $attributes['amount'],
                'paid_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
                'status' => 'paid',
            ];
        });
    }

    /**
     * Indicate that the fee is partially paid.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $partialAmount = $this->faker->randomFloat(2, 100, $attributes['amount'] - 100);
            return [
                'paid_amount' => $partialAmount,
                'paid_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
                'status' => 'partial',
            ];
        });
    }

    /**
     * Indicate that the fee is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-3 months', '-1 week'),
            'paid_amount' => 0,
            'paid_date' => null,
            'status' => 'pending',
            'late_fee' => $this->faker->randomFloat(2, 100, 1000),
        ]);
    }

    /**
     * Indicate that the fee is for tuition.
     */
    public function tuition(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_type' => 'tuition',
            'amount' => $this->faker->randomFloat(2, 3000, 8000),
        ]);
    }

    /**
     * Indicate that the fee is for admission.
     */
    public function admission(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_type' => 'admission',
            'amount' => $this->faker->randomFloat(2, 5000, 15000),
        ]);
    }

    /**
     * Indicate that the fee is for exam.
     */
    public function exam(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_type' => 'exam',
            'amount' => $this->faker->randomFloat(2, 500, 2000),
        ]);
    }

    /**
     * Indicate that the fee is for transport.
     */
    public function transport(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_type' => 'transport',
            'amount' => $this->faker->randomFloat(2, 1000, 3000),
        ]);
    }

    /**
     * Create a fee with high amount.
     */
    public function highAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 8000, 20000),
        ]);
    }

    /**
     * Create a fee with low amount.
     */
    public function lowAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 500, 2000),
        ]);
    }
}