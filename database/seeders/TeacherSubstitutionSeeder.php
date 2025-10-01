<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeacherSubstitution;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\User;
use Carbon\Carbon;

class TeacherSubstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required data
        $teachers = Teacher::all();
        $classes = ClassModel::all();
        $users = User::all();

        if ($teachers->isEmpty() || $classes->isEmpty() || $users->isEmpty()) {
            $this->command->info('Required data not found. Please run TeacherSeeder, ClassSeeder, and UserSeeder first.');
            return;
        }

        $subjects = [
            'Mathematics', 'Science', 'English', 'Hindi', 'Social Studies',
            'Physics', 'Chemistry', 'Biology', 'History', 'Geography',
            'Computer Science', 'Physical Education', 'Art', 'Music'
        ];

        $reasons = [
            'Sick leave',
            'Personal emergency',
            'Medical appointment',
            'Family function',
            'Training program',
            'Official meeting',
            'Sudden illness',
            'Transport issue',
            'Power outage at home',
            'Child care emergency'
        ];

        // Create substitution requests for the past week and next 2 weeks
        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now()->addWeeks(2);

        $currentDate = $startDate->copy();
        $substitutionCount = 0;

        while ($currentDate->lte($endDate) && $substitutionCount < 50) {
            // Skip weekends
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // Create 1-3 substitution requests per day
            $dailyRequests = rand(1, 3);

            for ($i = 0; $i < $dailyRequests; $i++) {
                $absentTeacher = $teachers->random();
                $class = $classes->random();
                $requestedBy = $users->random();
                
                // Generate random time slots
                $startHour = rand(8, 15); // 8 AM to 3 PM
                $startMinute = rand(0, 1) * 30; // 0 or 30 minutes
                $duration = rand(1, 3); // 1-3 hours
                
                $startTime = sprintf('%02d:%02d', $startHour, $startMinute);
                $endTime = Carbon::createFromFormat('H:i', $startTime)->addHours($duration)->format('H:i');

                // Determine status based on date
                $status = 'pending';
                $substituteTeacher = null;
                $assignedBy = null;
                $assignedAt = null;
                $completedAt = null;

                if ($currentDate->isPast()) {
                    // Past dates should be completed or assigned
                    $status = rand(0, 1) ? 'completed' : 'assigned';
                    $substituteTeacher = $teachers->where('id', '!=', $absentTeacher->id)->random();
                    $assignedBy = $users->random();
                    $assignedAt = $currentDate->copy()->addHours(rand(1, 6));
                    
                    if ($status === 'completed') {
                        $completedAt = $assignedAt->copy()->addHours(rand(1, 8));
                    }
                } elseif ($currentDate->isToday()) {
                    // Today's requests might be assigned
                    if (rand(0, 1)) {
                        $status = 'assigned';
                        $substituteTeacher = $teachers->where('id', '!=', $absentTeacher->id)->random();
                        $assignedBy = $users->random();
                        $assignedAt = now()->subHours(rand(1, 6));
                    }
                }

                $substitution = TeacherSubstitution::create([
                    'absent_teacher_id' => $absentTeacher->id,
                    'substitute_teacher_id' => $substituteTeacher?->id,
                    'class_id' => $class->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'subject' => $subjects[array_rand($subjects)],
                    'status' => $status,
                    'reason' => $reasons[array_rand($reasons)],
                    'notes' => $this->generateNotes($status),
                    'priority' => $this->getRandomPriority(),
                    'is_emergency' => rand(0, 10) === 0, // 10% chance of emergency
                    'requested_at' => $currentDate->copy()->subHours(rand(1, 24)),
                    'requested_by' => $requestedBy->id,
                    'assigned_by' => $assignedBy?->id,
                    'assigned_at' => $assignedAt,
                    'completed_at' => $completedAt,
                ]);

                $substitutionCount++;
                
                if ($substitutionCount % 10 === 0) {
                    $this->command->info("Created {$substitutionCount} substitution requests...");
                }
            }

            $currentDate->addDay();
        }

        $this->command->info("Teacher substitution seeded successfully! Created {$substitutionCount} substitution requests.");
    }

    /**
     * Generate notes based on status
     */
    private function generateNotes(string $status): ?string
    {
        $notes = [
            'pending' => [
                'Urgent substitution required',
                'Please assign as soon as possible',
                'Important class - exam preparation',
                'Regular class schedule',
                null
            ],
            'assigned' => [
                'Substitute teacher confirmed',
                'All materials provided to substitute',
                'Class informed about substitute teacher',
                'Lesson plan shared',
                null
            ],
            'completed' => [
                'Class conducted successfully',
                'All topics covered as planned',
                'Students were cooperative',
                'Substitute teacher did excellent job',
                'Minor adjustments made to lesson plan',
                null
            ]
        ];

        $statusNotes = $notes[$status] ?? [null];
        return $statusNotes[array_rand($statusNotes)];
    }

    /**
     * Get random priority
     */
    private function getRandomPriority(): string
    {
        $priorities = ['low', 'medium', 'high'];
        $weights = [50, 35, 15]; // 50% low, 35% medium, 15% high
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($priorities as $index => $priority) {
            $cumulative += $weights[$index];
            if ($random <= $cumulative) {
                return $priority;
            }
        }
        
        return 'medium';
    }
}