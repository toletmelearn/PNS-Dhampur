<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Role;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserWorkflowTest extends TestCase
{
    use  WithFaker;

    protected $admin;
    protected $teacher;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $teacherRole = Role::create(['name' => 'teacher', 'display_name' => 'Teacher']);
        $studentRole = Role::create(['name' => 'student', 'display_name' => 'Student']);

        // Create test users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);

        $this->teacher = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'role_id' => $teacherRole->id,
            'is_active' => true
        ]);

        $this->student = User::create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_complete_student_registration_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Navigate to student registration
        $response = $this->get('/admin/students/create');
        $response->assertStatus(200);
        $response->assertSee('Student Registration');

        // Step 2: Submit student registration form
        $studentData = [
            'name' => 'John Doe',
            'email' => 'john.doe@test.com',
            'phone' => '9876543210',
            'aadhaar' => '123456789012',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '001',
            'father_name' => 'John Father',
            'mother_name' => 'John Mother',
            'address' => '123 Test Street',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => now()->format('Y-m-d')
        ];

        $response = $this->post('/admin/students', $studentData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 3: Verify student was created
        $this->assertDatabaseHas('students', [
            'name' => 'John Doe',
            'email' => 'john.doe@test.com',
            'aadhaar' => '123456789012'
        ]);

        // Step 4: View student profile
        $student = Student::where('email', 'john.doe@test.com')->first();
        $response = $this->get("/admin/students/{$student->id}");
        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }

    /** @test */
    public function teacher_can_complete_attendance_workflow()
    {
        $this->actingAs($this->teacher);

        // Create a student for attendance
        $student = Student::create([
            'name' => 'Test Student',
            'email' => 'test.student@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '001',
            'admission_no' => 'ADM001',
            'is_active' => true
        ]);

        // Step 1: Navigate to attendance page
        $response = $this->get('/teacher/attendance');
        $response->assertStatus(200);

        // Step 2: Mark attendance
        $attendanceData = [
            'date' => now()->format('Y-m-d'),
            'class_id' => 1,
            'section' => 'A',
            'attendance' => [
                $student->id => 'present'
            ]
        ];

        $response = $this->post('/teacher/attendance', $attendanceData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 3: Verify attendance was recorded
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        // Step 4: View attendance report
        $response = $this->get('/teacher/attendance/report?class_id=1&month=' . now()->format('Y-m'));
        $response->assertStatus(200);
        $response->assertSee('Test Student');
    }

    /** @test */
    public function admin_can_complete_exam_management_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create exam
        $examData = [
            'name' => 'Mid Term Exam',
            'class_id' => 1,
            'subject' => 'Mathematics',
            'exam_date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_marks' => 100,
            'passing_marks' => 35
        ];

        $response = $this->post('/admin/exams', $examData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 2: Verify exam was created
        $exam = Exam::where('name', 'Mid Term Exam')->first();
        $this->assertNotNull($exam);

        // Step 3: Create student for result entry
        $student = Student::create([
            'name' => 'Exam Student',
            'email' => 'exam.student@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '002',
            'admission_no' => 'ADM002',
            'is_active' => true
        ]);

        // Step 4: Enter exam results
        $resultData = [
            'exam_id' => $exam->id,
            'results' => [
                $student->id => [
                    'marks_obtained' => 85,
                    'remarks' => 'Good performance'
                ]
            ]
        ];

        $response = $this->post('/admin/exam-results', $resultData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 5: Verify result was recorded
        $this->assertDatabaseHas('results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks_obtained' => 85
        ]);

        // Step 6: Generate result report
        $response = $this->get("/admin/exams/{$exam->id}/results");
        $response->assertStatus(200);
        $response->assertSee('Exam Student');
        $response->assertSee('85');
    }

    /** @test */
    public function admin_can_complete_fee_management_workflow()
    {
        $this->actingAs($this->admin);

        // Create student for fee management
        $student = Student::create([
            'name' => 'Fee Student',
            'email' => 'fee.student@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '003',
            'admission_no' => 'ADM003',
            'is_active' => true
        ]);

        // Step 1: Create fee structure
        $feeData = [
            'class_id' => 1,
            'fee_type' => 'tuition',
            'amount' => 5000,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'academic_year' => now()->format('Y')
        ];

        $response = $this->post('/admin/fees', $feeData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 2: Verify fee was created
        $fee = Fee::where('class_id', 1)->where('fee_type', 'tuition')->first();
        $this->assertNotNull($fee);

        // Step 3: Record fee payment
        $paymentData = [
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount_paid' => 5000,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
            'receipt_number' => 'RCP001'
        ];

        $response = $this->post('/admin/fee-payments', $paymentData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 4: Verify payment was recorded
        $this->assertDatabaseHas('fee_payments', [
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount_paid' => 5000,
            'receipt_number' => 'RCP001'
        ]);

        // Step 5: Generate fee report
        $response = $this->get('/admin/fees/report?class_id=1');
        $response->assertStatus(200);
        $response->assertSee('Fee Student');
    }

    /** @test */
    public function student_can_complete_profile_and_dashboard_workflow()
    {
        // Create student record for the user
        $studentRecord = Student::create([
            'name' => $this->student->name,
            'email' => $this->student->email,
            'user_id' => $this->student->id,
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '004',
            'admission_no' => 'ADM004',
            'is_active' => true
        ]);

        $this->actingAs($this->student);

        // Step 1: Access student dashboard
        $response = $this->get('/student/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Student Dashboard');

        // Step 2: View profile
        $response = $this->get('/student/profile');
        $response->assertStatus(200);
        $response->assertSee($this->student->name);

        // Step 3: Update profile
        $profileData = [
            'phone' => '9876543210',
            'address' => 'Updated Address',
            'emergency_contact' => '9876543211'
        ];

        $response = $this->put('/student/profile', $profileData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 4: View attendance
        $response = $this->get('/student/attendance');
        $response->assertStatus(200);

        // Step 5: View exam results
        $response = $this->get('/student/results');
        $response->assertStatus(200);

        // Step 6: View fee status
        $response = $this->get('/student/fees');
        $response->assertStatus(200);
    }

    /** @test */
    public function bulk_operations_workflow_works_correctly()
    {
        $this->actingAs($this->admin);

        // Step 1: Bulk student import
        Storage::fake('local');
        
        $csvContent = "name,email,class_id,section,roll_number,father_name,mother_name\n";
        $csvContent .= "Student 1,student1@test.com,1,A,001,Father 1,Mother 1\n";
        $csvContent .= "Student 2,student2@test.com,1,A,002,Father 2,Mother 2\n";
        $csvContent .= "Student 3,student3@test.com,1,B,003,Father 3,Mother 3\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->post('/admin/students/bulk-import', [
            'file' => $file,
            'class_id' => 1
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify students were imported
        $this->assertDatabaseHas('students', ['name' => 'Student 1']);
        $this->assertDatabaseHas('students', ['name' => 'Student 2']);
        $this->assertDatabaseHas('students', ['name' => 'Student 3']);

        // Step 2: Bulk attendance marking
        $students = Student::where('class_id', 1)->get();
        $attendanceData = [
            'date' => now()->format('Y-m-d'),
            'class_id' => 1,
            'attendance' => []
        ];

        foreach ($students as $student) {
            $attendanceData['attendance'][$student->id] = 'present';
        }

        $response = $this->post('/admin/attendance/bulk', $attendanceData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify attendance was marked for all students
        foreach ($students as $student) {
            $this->assertDatabaseHas('attendances', [
                'student_id' => $student->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'present'
            ]);
        }
    }

    /** @test */
    public function error_handling_workflow_works_correctly()
    {
        $this->actingAs($this->admin);

        // Test validation errors
        $response = $this->post('/admin/students', [
            'name' => '', // Required field missing
            'email' => 'invalid-email', // Invalid email
            'aadhaar' => '123' // Invalid Aadhaar
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'aadhaar']);

        // Test duplicate entry error
        Student::create([
            'name' => 'Existing Student',
            'email' => 'existing@test.com',
            'aadhaar' => '123456789012',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '001',
            'admission_no' => 'ADM001',
            'is_active' => true
        ]);

        $response = $this->post('/admin/students', [
            'name' => 'Another Student',
            'email' => 'existing@test.com', // Duplicate email
            'aadhaar' => '123456789012', // Duplicate Aadhaar
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '002',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'address' => 'Address',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => now()->format('Y-m-d')
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function permission_based_access_workflow_works_correctly()
    {
        // Test admin access
        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // Test teacher access to admin routes (should be denied)
        $this->actingAs($this->teacher);
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);

        // Test teacher access to teacher routes
        $response = $this->get('/teacher/dashboard');
        $response->assertStatus(200);

        // Test student access to admin routes (should be denied)
        $this->actingAs($this->student);
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);

        // Test student access to teacher routes (should be denied)
        $response = $this->get('/teacher/dashboard');
        $response->assertStatus(403);

        // Test student access to student routes
        $response = $this->get('/student/dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function api_workflow_integration_works_correctly()
    {
        $this->actingAs($this->admin);

        // Test API authentication
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->admin->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');

        // Test authenticated API request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/students');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test API student creation
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/students', [
            'name' => 'API Student',
            'email' => 'api.student@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '005',
            'father_name' => 'API Father',
            'mother_name' => 'API Mother',
            'address' => 'API Address',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }
}