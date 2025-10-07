<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $status = $this->faker->randomElement([
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_EXCUSED,
            Attendance::STATUS_SICK
        ]);

        // Generate check-in and check-out times based on status
        $checkInTime = null;
        $checkOutTime = null;
        $lateMinutes = 0;
        $earlyDepartureMinutes = 0;

        if ($status === Attendance::STATUS_PRESENT || $status === Attendance::STATUS_LATE) {
            $baseCheckIn = Carbon::parse($date)->setTime(8, 0); // 8:00 AM base time
            
            if ($status === Attendance::STATUS_LATE) {
                $lateMinutes = $this->faker->numberBetween(5, 60);
                $checkInTime = $baseCheckIn->addMinutes($lateMinutes);
            } else {
                $checkInTime = $baseCheckIn->addMinutes($this->faker->numberBetween(-10, 10));
            }

            // Check-out time (usually around 3:00 PM)
            $baseCheckOut = Carbon::parse($date)->setTime(15, 0);
            $checkOutTime = $baseCheckOut->addMinutes($this->faker->numberBetween(-30, 30));
            
            // Calculate early departure if check-out is before 2:30 PM
            if ($checkOutTime->lt(Carbon::parse($date)->setTime(14, 30))) {
                $earlyDepartureMinutes = Carbon::parse($date)->setTime(15, 0)->diffInMinutes($checkOutTime);
            }
        }

        return [
            'student_id' => Student::factory(),
            'class_id' => ClassModel::factory(),
            'date' => $date->format('Y-m-d'),
            'status' => $status,
            'marked_by' => User::factory(),
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'remarks' => $status === Attendance::STATUS_ABSENT ? $this->faker->optional()->sentence() : null,
            'academic_year' => $this->faker->numberBetween(2023, 2025) . '-' . ($this->faker->numberBetween(2024, 2026)),
            'month' => (int) $date->format('n'),
            'week_number' => (int) $date->format('W'),
            'is_holiday' => false,
            'attendance_type' => $this->faker->randomElement([
                Attendance::TYPE_REGULAR,
                Attendance::TYPE_MAKEUP,
                Attendance::TYPE_SPECIAL
            ])
        ];
    }

    /**
     * Create attendance for a specific student and date
     */
    public function forStudent($studentId): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $studentId,
        ]);
    }

    /**
     * Create attendance for a specific class and date
     */
    public function forClass($classId): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $classId,
        ]);
    }

    /**
     * Create attendance for a specific date
     */
    public function forDate($date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => Carbon::parse($date)->format('Y-m-d'),
            'month' => (int) Carbon::parse($date)->format('n'),
            'week_number' => (int) Carbon::parse($date)->format('W'),
        ]);
    }

    /**
     * Create present attendance
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_PRESENT,
            'check_in_time' => Carbon::parse($attributes['date'])->setTime(8, 0)->addMinutes($this->faker->numberBetween(-10, 10)),
            'check_out_time' => Carbon::parse($attributes['date'])->setTime(15, 0)->addMinutes($this->faker->numberBetween(-30, 30)),
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'remarks' => null,
        ]);
    }

    /**
     * Create absent attendance
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_ABSENT,
            'check_in_time' => null,
            'check_out_time' => null,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'remarks' => $this->faker->optional()->sentence(),
        ]);
    }

    /**
     * Create late attendance
     */
    public function late(): static
    {
        $lateMinutes = $this->faker->numberBetween(5, 60);
        
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_LATE,
            'check_in_time' => Carbon::parse($attributes['date'])->setTime(8, 0)->addMinutes($lateMinutes),
            'check_out_time' => Carbon::parse($attributes['date'])->setTime(15, 0)->addMinutes($this->faker->numberBetween(-30, 30)),
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => 0,
            'remarks' => null,
        ]);
    }

    /**
     * Create holiday attendance
     */
    public function holiday(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_HOLIDAY,
            'check_in_time' => null,
            'check_out_time' => null,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'is_holiday' => true,
            'remarks' => 'Holiday',
        ]);
    }
}