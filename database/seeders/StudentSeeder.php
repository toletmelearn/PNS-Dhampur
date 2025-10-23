<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\ClassModel;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $classes = ClassModel::all();
        
        if ($classes->isEmpty()) {
            $this->command->info('No classes found. Please run ClassSeeder first.');
            return;
        }

        $studentNames = [
            ['name' => 'Aarav Sharma', 'father' => 'Rajesh Sharma', 'mother' => 'Priya Sharma'],
            ['name' => 'Vivaan Singh', 'father' => 'Amit Singh', 'mother' => 'Sunita Singh'],
            ['name' => 'Aditya Kumar', 'father' => 'Suresh Kumar', 'mother' => 'Meera Kumar'],
            ['name' => 'Vihaan Gupta', 'father' => 'Rakesh Gupta', 'mother' => 'Kavita Gupta'],
            ['name' => 'Arjun Verma', 'father' => 'Vinod Verma', 'mother' => 'Sita Verma'],
            ['name' => 'Sai Patel', 'father' => 'Mahesh Patel', 'mother' => 'Nisha Patel'],
            ['name' => 'Reyansh Jain', 'father' => 'Deepak Jain', 'mother' => 'Pooja Jain'],
            ['name' => 'Ayaan Khan', 'father' => 'Salim Khan', 'mother' => 'Fatima Khan'],
            ['name' => 'Krishna Yadav', 'father' => 'Ram Yadav', 'mother' => 'Gita Yadav'],
            ['name' => 'Ishaan Agarwal', 'father' => 'Mohan Agarwal', 'mother' => 'Ritu Agarwal'],
            ['name' => 'Ananya Sharma', 'father' => 'Vikash Sharma', 'mother' => 'Anjali Sharma'],
            ['name' => 'Diya Singh', 'father' => 'Ravi Singh', 'mother' => 'Neha Singh'],
            ['name' => 'Aadhya Kumar', 'father' => 'Ashok Kumar', 'mother' => 'Rekha Kumar'],
            ['name' => 'Pihu Gupta', 'father' => 'Sanjay Gupta', 'mother' => 'Shweta Gupta'],
            ['name' => 'Saanvi Verma', 'father' => 'Manoj Verma', 'mother' => 'Usha Verma'],
            ['name' => 'Ira Patel', 'father' => 'Kiran Patel', 'mother' => 'Hema Patel'],
            ['name' => 'Myra Jain', 'father' => 'Anil Jain', 'mother' => 'Seema Jain'],
            ['name' => 'Anika Khan', 'father' => 'Arif Khan', 'mother' => 'Zara Khan'],
            ['name' => 'Navya Yadav', 'father' => 'Shyam Yadav', 'mother' => 'Radha Yadav'],
            ['name' => 'Kiara Agarwal', 'father' => 'Sunil Agarwal', 'mother' => 'Manju Agarwal'],
        ];

        $counter = 1;
        foreach ($classes as $class) {
            // Create 8-12 students per class
            $studentsPerClass = rand(8, 12);
            
            for ($i = 0; $i < $studentsPerClass; $i++) {
                if ($counter > count($studentNames)) {
                    // If we run out of names, generate generic ones
                    $studentData = [
                        'name' => 'Student ' . $counter,
                        'father' => 'Father ' . $counter,
                        'mother' => 'Mother ' . $counter
                    ];
                } else {
                    $studentData = $studentNames[$counter - 1];
                }

                $admissionNo = 'PNS' . date('Y') . str_pad($counter, 4, '0', STR_PAD_LEFT);
                $aadhaar = '1234' . str_pad($counter, 8, '0', STR_PAD_LEFT);
                
                // Generate random DOB based on class level
                $classLevel = (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT);
                if ($classLevel <= 5) {
                    $age = rand(5, 10);
                } elseif ($classLevel <= 8) {
                    $age = rand(11, 14);
                } elseif ($classLevel <= 10) {
                    $age = rand(15, 16);
                } else {
                    $age = rand(17, 18);
                }
                
                $dob = Carbon::now()->subYears($age)->subDays(rand(0, 365));

                Student::create([
                    'admission_no' => $admissionNo,
                    'admission_number' => $admissionNo,
                    'name' => $studentData['name'],
                    'father_name' => $studentData['father'],
                    'mother_name' => $studentData['mother'],
                    'dob' => $dob,
                    'aadhaar' => $aadhaar,
                    'class_id' => $class->id,
                    'documents' => [
                        'birth_cert' => 'documents/students/birth_cert_' . $counter . '.pdf',
                        'aadhaar' => 'documents/students/aadhaar_' . $counter . '.pdf',
                        'photo' => 'documents/students/photo_' . $counter . '.jpg',
                    ],
                    'documents_verified_data' => [],
                    'verification_status' => 'pending',
                    'status' => 'active',
                    'verified' => false,
                    'meta' => [
                        'blood_group' => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->random(),
                        'emergency_contact' => '9876543' . str_pad($counter % 100, 3, '0', STR_PAD_LEFT),
                        'address' => 'Address ' . $counter . ', Dhampur, UP',
                    ],
                ]);

                $this->command->info("Created student: {$studentData['name']} (Admission: $admissionNo)");
                $counter++;
            }
        }

        $this->command->info('Students seeded successfully!');
    }
}
