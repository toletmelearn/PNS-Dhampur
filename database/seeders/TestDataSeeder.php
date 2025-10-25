<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->createRoles();
        $this->createSchools();
        $this->createUsers();
        $this->createClasses();
        $this->createSections();
        $this->createStudents();
        $this->createTeachers();
        $this->createUserRoleAssignments();
    }

    private function createRoles()
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'System Super Administrator'],
            ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'School Administrator'],
            ['name' => 'principal', 'display_name' => 'Principal', 'description' => 'School Principal'],
            ['name' => 'teacher', 'display_name' => 'Teacher', 'description' => 'School Teacher'],
            ['name' => 'student', 'display_name' => 'Student', 'description' => 'School Student'],
            ['name' => 'parent', 'display_name' => 'Parent', 'description' => 'Student Parent'],
        ];
        
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }
    }

    private function createSchools()
    {
        $schools = [
            [
                'name' => 'PNS Dhampur',
                'code' => 'PNS001',
                'address' => 'Dhampur, Uttar Pradesh',
                'phone' => '01234567890',
                'email' => 'info@pnsdhampur.edu',
                'website' => 'https://pnsdhampur.edu',
                'principal_name' => 'Dr. Principal Name',
                'established_date' => '1990-01-01',
                'status' => 'active',
                'settings' => json_encode(['timezone' => 'Asia/Kolkata']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($schools as $school) {
            DB::table('schools')->insertOrIgnore($school);
        }
    }

    private function createUsers()
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543210',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'School Admin',
                'email' => 'admin@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543211',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Principal',
                'email' => 'principal@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543212',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mrs. Sunita Verma',
                'email' => 'teacher1@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543213',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rahul Sharma',
                'email' => 'student1@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543214',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mr. Rajesh Sharma',
                'email' => 'parent1@pnsdhampur.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543215',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($users as $user) {
            DB::table('users')->insertOrIgnore($user);
        }
    }

    private function createClasses()
    {
        $classes = [
            [
                'class_name' => 'Class 10',
                'class_code' => 'CLS10',
                'description' => 'Tenth Standard',
                'grade_level' => 10,
                'academic_year' => '2024-25',
                'max_students' => 50,
                'current_students' => 0,
                'monthly_fee' => 2000.00,
                'admission_fee' => 5000.00,
                'annual_fee' => 10000.00,
                'status' => 'active',
                'is_promoted' => false,
                'admission_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'class_name' => 'Class 9',
                'class_code' => 'CLS09',
                'description' => 'Ninth Standard',
                'grade_level' => 9,
                'academic_year' => '2024-25',
                'max_students' => 50,
                'current_students' => 0,
                'monthly_fee' => 1800.00,
                'admission_fee' => 4500.00,
                'annual_fee' => 9000.00,
                'status' => 'active',
                'is_promoted' => false,
                'admission_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($classes as $class) {
            DB::table('classes')->insertOrIgnore($class);
        }
    }

    private function createSections()
    {
        $sections = [
            [
                'name' => 'A',
                'class_id' => 1,
                'capacity' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'B',
                'class_id' => 1,
                'capacity' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'A',
                'class_id' => 2,
                'capacity' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($sections as $section) {
            DB::table('sections')->insertOrIgnore($section);
        }
    }

    private function createStudents()
    {
        $students = [
            [
                'admission_number' => 'PNS001',
                'name' => 'Rahul Sharma',
                'father_name' => 'Mr. Rajesh Sharma',
                'mother_name' => 'Mrs. Priya Sharma',
                'dob' => '2010-05-15',
                'gender' => 'male',
                'class_id' => 1,
                'section_id' => 1,
                'roll_number' => '001',
                'phone' => '9876543214',
                'email' => 'student1@pnsdhampur.edu',
                'address' => '123 Student Street, Dhampur',
                'user_id' => 5,
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'admission_number' => 'PNS002',
                'name' => 'Priya Gupta',
                'father_name' => 'Mr. Suresh Gupta',
                'mother_name' => 'Mrs. Meera Gupta',
                'dob' => '2010-08-20',
                'gender' => 'female',
                'class_id' => 1,
                'section_id' => 1,
                'roll_number' => '002',
                'phone' => '9876543216',
                'email' => 'priya.gupta@pnsdhampur.edu',
                'address' => '456 Student Avenue, Dhampur',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($students as $student) {
            DB::table('students')->insertOrIgnore($student);
        }
    }

    private function createTeachers()
    {
        $teachers = [
            [
                'user_id' => 4,
                'qualification' => 'M.Sc Mathematics, B.Ed',
                'experience_years' => 10,
                'salary' => 35000,
                'joining_date' => '2020-06-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($teachers as $teacher) {
            DB::table('teachers')->insertOrIgnore($teacher);
        }
    }

    private function createUserRoleAssignments()
    {
        $assignments = [
            ['user_id' => 1, 'role_id' => 1, 'school_id' => 1], // Super Admin
            ['user_id' => 2, 'role_id' => 2, 'school_id' => 1], // Admin
            ['user_id' => 3, 'role_id' => 3, 'school_id' => 1], // Principal
            ['user_id' => 4, 'role_id' => 4, 'school_id' => 1], // Teacher
            ['user_id' => 5, 'role_id' => 5, 'school_id' => 1], // Student
            ['user_id' => 6, 'role_id' => 6, 'school_id' => 1], // Parent
        ];
        
        foreach ($assignments as $assignment) {
            DB::table('user_role_assignments')->insertOrIgnore($assignment);
        }
    }
}