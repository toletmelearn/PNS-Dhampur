<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if we have classes and teachers
        $classes = ClassModel::all();
        $teachers = Teacher::all();

        if ($classes->isEmpty() || $teachers->isEmpty()) {
            $this->command->info('Skipping SubjectSeeder: No classes or teachers found.');
            return;
        }

        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'description' => 'Mathematics subject covering algebra, geometry, and calculus'],
            ['name' => 'English', 'code' => 'ENG', 'description' => 'English language and literature'],
            ['name' => 'Science', 'code' => 'SCI', 'description' => 'General science covering physics, chemistry, and biology'],
            ['name' => 'Social Studies', 'code' => 'SS', 'description' => 'History, geography, and civics'],
            ['name' => 'Hindi', 'code' => 'HIN', 'description' => 'Hindi language and literature'],
            ['name' => 'Computer Science', 'code' => 'CS', 'description' => 'Computer programming and digital literacy'],
            ['name' => 'Physical Education', 'code' => 'PE', 'description' => 'Physical fitness and sports'],
            ['name' => 'Art', 'code' => 'ART', 'description' => 'Drawing, painting, and creative arts'],
            ['name' => 'Music', 'code' => 'MUS', 'description' => 'Vocal and instrumental music'],
            ['name' => 'Environmental Studies', 'code' => 'EVS', 'description' => 'Environmental awareness and conservation'],
        ];

        foreach ($subjects as $subjectData) {
            // Assign random class and teacher
            $randomClass = $classes->random();
            $randomTeacher = $teachers->random();

            Subject::create([
                'name' => $subjectData['name'],
                'code' => $subjectData['code'],
                'description' => $subjectData['description'],
                'class_id' => $randomClass->id,
                'teacher_id' => $randomTeacher->id,
                'is_active' => true,
            ]);
        }

        $this->command->info('SubjectSeeder completed: Created ' . count($subjects) . ' subjects.');
    }
}
