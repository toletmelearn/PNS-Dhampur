<?php

namespace Database\Factories;

use App\Models\BiometricAttendance;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class BiometricAttendanceFactory extends Factory
{
    protected $model = BiometricAttendance::class;

    public function definition()
    {
        return [
            'teacher_id' => Teacher::factory(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'check_in_time' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'check_out_time' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['present', 'absent']),
            'working_hours' => $this->faker->randomFloat(2, 0, 12),
            'is_late' => $this->faker->boolean(20), // 20% chance of being late
            'is_early_departure' => $this->faker->boolean(10), // 10% chance of early departure
            'biometric_data' => json_encode(['type' => 'fingerprint', 'quality' => $this->faker->numberBetween(70, 100)]),
            'device_id' => $this->faker->randomElement(['DEV001', 'DEV002', 'CSV_IMPORT']),
            'check_in_location' => $this->faker->randomElement(['Main Campus', 'Branch Office']),
            'check_out_location' => $this->faker->optional()->randomElement(['Main Campus', 'Branch Office']),
            'absence_reason' => $this->faker->optional()->randomElement(['Sick Leave', 'Personal Leave', 'Emergency']),
            'import_source' => $this->faker->randomElement(['csv', 'real_time_device']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}