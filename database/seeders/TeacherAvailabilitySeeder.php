<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeacherAvailability;
use App\Models\Teacher;
use Carbon\Carbon;

class TeacherAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teachers
        $teachers = Teacher::all();

        if ($teachers->isEmpty()) {
            $this->command->info('No teachers found. Please run TeacherSeeder first.');
            return;
        }

        // Create availability for the next 4 weeks
        $startDate = Carbon::now()->startOfWeek();
        $endDate = $startDate->copy()->addWeeks(4);

        $subjects = [
            'Mathematics', 'Science', 'English', 'Hindi', 'Social Studies',
            'Physics', 'Chemistry', 'Biology', 'History', 'Geography',
            'Computer Science', 'Physical Education', 'Art', 'Music'
        ];

        foreach ($teachers as $teacher) {
            $this->command->info("Creating availability for teacher: {$teacher->user->name}");

            // Create weekly schedule for each teacher
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                // Skip weekends (Saturday and Sunday)
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }

                // Morning session (8:00 AM - 12:00 PM)
                TeacherAvailability::create([
                    'teacher_id' => $teacher->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'status' => 'available',
                    'subject_expertise' => $this->getRandomSubjects($subjects, rand(2, 4)),
                    'notes' => 'Morning session availability',
                    'can_substitute' => true,
                    'max_substitutions_per_day' => rand(2, 4),
                ]);

                // Afternoon session (1:00 PM - 5:00 PM)
                TeacherAvailability::create([
                    'teacher_id' => $teacher->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => '13:00',
                    'end_time' => '17:00',
                    'status' => 'available',
                    'subject_expertise' => $this->getRandomSubjects($subjects, rand(2, 4)),
                    'notes' => 'Afternoon session availability',
                    'can_substitute' => true,
                    'max_substitutions_per_day' => rand(2, 4),
                ]);

                // Randomly make some teachers unavailable on certain days
                if (rand(1, 10) <= 2) { // 20% chance of being unavailable
                    // Update one of the sessions to be unavailable
                    $sessionToUpdate = rand(0, 1) ? 'morning' : 'afternoon';
                    $timeRange = $sessionToUpdate === 'morning' ? ['08:00', '12:00'] : ['13:00', '17:00'];
                    
                    TeacherAvailability::where('teacher_id', $teacher->id)
                                      ->where('date', $currentDate->format('Y-m-d'))
                                      ->where('start_time', $timeRange[0])
                                      ->where('end_time', $timeRange[1])
                                      ->update([
                                          'status' => rand(0, 1) ? 'busy' : 'leave',
                                          'can_substitute' => false,
                                          'notes' => rand(0, 1) ? 'Personal work' : 'On leave',
                                      ]);
                }

                $currentDate->addDay();
            }
        }

        $this->command->info('Teacher availability seeded successfully!');
    }

    /**
     * Get random subjects from the list
     */
    private function getRandomSubjects(array $subjects, int $count): array
    {
        $shuffled = collect($subjects)->shuffle();
        return $shuffled->take($count)->values()->toArray();
    }
}