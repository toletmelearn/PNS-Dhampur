<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample teacher data
        $teachersData = [
            [
                'name' => 'Dr. Rajesh Kumar',
                'email' => 'rajesh.kumar@pnsdhampur.edu',
                'qualification' => 'M.Sc. Mathematics, B.Ed.',
                'experience_years' => 15,
                'salary' => 45000,
                'joining_date' => '2010-06-15',
                'subjects' => ['Mathematics', 'Physics'],
            ],
            [
                'name' => 'Mrs. Priya Sharma',
                'email' => 'priya.sharma@pnsdhampur.edu',
                'qualification' => 'M.A. English, B.Ed.',
                'experience_years' => 12,
                'salary' => 42000,
                'joining_date' => '2012-04-10',
                'subjects' => ['English', 'Hindi'],
            ],
            [
                'name' => 'Mr. Amit Singh',
                'email' => 'amit.singh@pnsdhampur.edu',
                'qualification' => 'M.Sc. Chemistry, B.Ed.',
                'experience_years' => 8,
                'salary' => 38000,
                'joining_date' => '2016-07-20',
                'subjects' => ['Chemistry', 'Science'],
            ],
            [
                'name' => 'Mrs. Sunita Gupta',
                'email' => 'sunita.gupta@pnsdhampur.edu',
                'qualification' => 'M.A. History, B.Ed.',
                'experience_years' => 18,
                'salary' => 48000,
                'joining_date' => '2008-03-25',
                'subjects' => ['History', 'Social Studies'],
            ],
            [
                'name' => 'Mr. Vikash Yadav',
                'email' => 'vikash.yadav@pnsdhampur.edu',
                'qualification' => 'M.Sc. Biology, B.Ed.',
                'experience_years' => 10,
                'salary' => 40000,
                'joining_date' => '2014-08-12',
                'subjects' => ['Biology', 'Science'],
            ],
            [
                'name' => 'Mrs. Kavita Verma',
                'email' => 'kavita.verma@pnsdhampur.edu',
                'qualification' => 'M.A. Geography, B.Ed.',
                'experience_years' => 14,
                'salary' => 43000,
                'joining_date' => '2011-01-18',
                'subjects' => ['Geography', 'Social Studies'],
            ],
            [
                'name' => 'Mr. Deepak Mishra',
                'email' => 'deepak.mishra@pnsdhampur.edu',
                'qualification' => 'MCA, B.Ed.',
                'experience_years' => 6,
                'salary' => 35000,
                'joining_date' => '2018-09-05',
                'subjects' => ['Computer Science', 'Mathematics'],
            ],
            [
                'name' => 'Mrs. Neha Agarwal',
                'email' => 'neha.agarwal@pnsdhampur.edu',
                'qualification' => 'M.Sc. Physics, B.Ed.',
                'experience_years' => 9,
                'salary' => 39000,
                'joining_date' => '2015-05-30',
                'subjects' => ['Physics', 'Mathematics'],
            ],
            [
                'name' => 'Mr. Ravi Tiwari',
                'email' => 'ravi.tiwari@pnsdhampur.edu',
                'qualification' => 'B.P.Ed., M.A. Physical Education',
                'experience_years' => 11,
                'salary' => 36000,
                'joining_date' => '2013-11-08',
                'subjects' => ['Physical Education', 'Sports'],
            ],
            [
                'name' => 'Mrs. Anita Joshi',
                'email' => 'anita.joshi@pnsdhampur.edu',
                'qualification' => 'M.A. Hindi, B.Ed.',
                'experience_years' => 16,
                'salary' => 46000,
                'joining_date' => '2009-02-14',
                'subjects' => ['Hindi', 'Sanskrit'],
            ],
        ];

        foreach ($teachersData as $teacherData) {
            $this->command->info("Creating teacher: {$teacherData['name']}");

            // Check if user already exists
            $existingUser = User::where('email', $teacherData['email'])->first();
            
            if ($existingUser) {
                $this->command->info("User {$teacherData['email']} already exists, skipping...");
                continue;
            }

            // Create user first
            $user = User::create([
                'name' => $teacherData['name'],
                'email' => $teacherData['email'],
                'password' => Hash::make('password123'), // Default password
                'role' => 'teacher',
            ]);

            // Create teacher record
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'qualification' => $teacherData['qualification'],
                'experience_years' => $teacherData['experience_years'],
                'salary' => $teacherData['salary'],
                'joining_date' => $teacherData['joining_date'],
                'documents' => [
                    'resume' => 'documents/teachers/resume_' . strtolower(str_replace(' ', '_', $teacherData['name'])) . '.pdf',
                    'certificates' => 'documents/teachers/certificates_' . strtolower(str_replace(' ', '_', $teacherData['name'])) . '.pdf',
                ],
            ]);

            // Assign to random classes (optional)
            $classes = ClassModel::inRandomOrder()->take(rand(1, 3))->get();
            foreach ($classes as $class) {
                // You might want to create a pivot table for teacher-class relationships
                // For now, we'll just associate the teacher with classes through the class_teacher_id field
                if (!$class->class_teacher_id) {
                    $class->update(['class_teacher_id' => $teacher->id]);
                }
            }
        }

        $this->command->info('Teachers seeded successfully!');
    }
}