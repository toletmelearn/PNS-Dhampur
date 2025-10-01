<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassModel;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            // Primary Classes
            ['name' => 'Class I', 'section' => 'A'],
            ['name' => 'Class I', 'section' => 'B'],
            ['name' => 'Class II', 'section' => 'A'],
            ['name' => 'Class II', 'section' => 'B'],
            ['name' => 'Class III', 'section' => 'A'],
            ['name' => 'Class III', 'section' => 'B'],
            ['name' => 'Class IV', 'section' => 'A'],
            ['name' => 'Class IV', 'section' => 'B'],
            ['name' => 'Class V', 'section' => 'A'],
            ['name' => 'Class V', 'section' => 'B'],
            
            // Middle Classes
            ['name' => 'Class VI', 'section' => 'A'],
            ['name' => 'Class VI', 'section' => 'B'],
            ['name' => 'Class VII', 'section' => 'A'],
            ['name' => 'Class VII', 'section' => 'B'],
            ['name' => 'Class VIII', 'section' => 'A'],
            ['name' => 'Class VIII', 'section' => 'B'],
            
            // Secondary Classes
            ['name' => 'Class IX', 'section' => 'A'],
            ['name' => 'Class IX', 'section' => 'B'],
            ['name' => 'Class X', 'section' => 'A'],
            ['name' => 'Class X', 'section' => 'B'],
            
            // Senior Secondary Classes
            ['name' => 'Class XI', 'section' => 'Science'],
            ['name' => 'Class XI', 'section' => 'Commerce'],
            ['name' => 'Class XI', 'section' => 'Arts'],
            ['name' => 'Class XII', 'section' => 'Science'],
            ['name' => 'Class XII', 'section' => 'Commerce'],
            ['name' => 'Class XII', 'section' => 'Arts'],
        ];

        foreach ($classes as $classData) {
            $this->command->info("Creating class: {$classData['name']} - {$classData['section']}");
            
            ClassModel::create([
                'name' => $classData['name'],
                'section' => $classData['section'],
                'class_teacher_id' => null, // Will be assigned by TeacherSeeder
            ]);
        }

        $this->command->info('Classes seeded successfully!');
    }
}