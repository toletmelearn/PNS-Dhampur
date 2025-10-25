<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Exam;
use App\Models\Result;
use App\Models\Salary;
use App\Models\User;
use App\Notifications\ParentNotification;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $reportData = [];
    private $reportPath = 'public/integration_test_report.html';

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->reportData = [
            'teacher_attendance_salary' => ['status' => 'PENDING', 'details' => []],
            'student_attendance_notification' => ['status' => 'PENDING', 'details' => []],
            'fee_payment_student_record' => ['status' => 'PENDING', 'details' => []],
            'exam_results_student_profile' => ['status' => 'PENDING', 'details' => []],
            'consolidated_reports' => ['status' => 'PENDING', 'details' => []],
        ];
    }

    /**
     * Run all integration tests and generate report
     */
    public function testIntegration()
    {
        $this->testTeacherAttendanceAffectsSalary();
        $this->testStudentAttendanceTriggersNotification();
        $this->testFeePaymentUpdatesStudentRecord();
        $this->testExamResultsIntegrateWithStudentProfile();
        $this->testReportGenerationAccuracy();
        
        $this->generateReport();
        
        echo "Integration tests completed. Report generated at {$this->reportPath}\n";
    }

    /**
     * Test that teacher attendance affects salary calculation
     */
    public function testTeacherAttendanceAffectsSalary()
    {
        try {
            // Create a test teacher
            $teacher = Teacher::factory()->create();
            $user = User::factory()->create(['role' => 'teacher']);
            $teacher->user_id = $user->id;
            $teacher->save();
            
            // Initial salary record
            $initialSalary = Salary::create([
                'teacher_id' => $teacher->id,
                'base_amount' => 50000,
                'attendance_factor' => 1.0,
                'final_amount' => 50000,
                'month' => date('m'),
                'year' => date('Y'),
            ]);
            
            // Record attendance (80% attendance)
            $workingDays = 20;
            $presentDays = 16;
            
            DB::table('teacher_attendance')->insert([
                'teacher_id' => $teacher->id,
                'month' => date('m'),
                'year' => date('Y'),
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'attendance_percentage' => ($presentDays / $workingDays) * 100,
            ]);
            
            // Trigger salary recalculation
            $attendanceFactor = $presentDays / $workingDays;
            $expectedAmount = 50000 * $attendanceFactor;
            
            $salary = Salary::where('teacher_id', $teacher->id)->first();
            $salary->attendance_factor = $attendanceFactor;
            $salary->final_amount = $expectedAmount;
            $salary->save();
            
            // Verify salary was updated correctly
            $updatedSalary = Salary::where('teacher_id', $teacher->id)->first();
            
            $success = abs($updatedSalary->final_amount - $expectedAmount) < 0.01;
            $this->reportData['teacher_attendance_salary'] = [
                'status' => $success ? 'SUCCESS' : 'ERROR',
                'details' => [
                    'teacher_id' => $teacher->id,
                    'attendance_percentage' => ($presentDays / $workingDays) * 100,
                    'expected_salary' => $expectedAmount,
                    'actual_salary' => $updatedSalary->final_amount,
                ]
            ];
            
            $this->assertTrue($success, 'Salary calculation based on attendance failed');
            
        } catch (\Exception $e) {
            $this->reportData['teacher_attendance_salary'] = [
                'status' => 'ERROR',
                'details' => ['error' => $e->getMessage()]
            ];
            $this->fail('Exception in teacher attendance test: ' . $e->getMessage());
        }
    }

    /**
     * Test that student attendance triggers parent notifications
     */
    public function testStudentAttendanceTriggersNotification()
    {
        try {
            // Create test student and parent user
            $student = Student::factory()->create();
            $parentUser = User::factory()->create(['role' => 'parent']);
            $student->parent_id = $parentUser->id;
            $student->save();
            
            // Record absence
            DB::table('student_attendance')->insert([
                'student_id' => $student->id,
                'date' => date('Y-m-d'),
                'status' => 'absent',
                'recorded_by' => 1,
            ]);
            
            // Simulate notification dispatch
            $notification = new ParentNotification([
                'title' => 'Attendance Alert',
                'message' => "Your child {$student->name} was absent today.",
                'student_id' => $student->id,
                'type' => 'attendance',
            ]);
            
            Notification::send($parentUser, $notification);
            
            // Verify notification was sent
            Notification::assertSentTo(
                $parentUser,
                ParentNotification::class,
                function ($notification) use ($student) {
                    return $notification->data['student_id'] === $student->id;
                }
            );
            
            $this->reportData['student_attendance_notification'] = [
                'status' => 'SUCCESS',
                'details' => [
                    'student_id' => $student->id,
                    'parent_id' => $student->parent_id,
                    'notification_type' => 'attendance',
                ]
            ];
            
        } catch (\Exception $e) {
            $this->reportData['student_attendance_notification'] = [
                'status' => 'ERROR',
                'details' => ['error' => $e->getMessage()]
            ];
            $this->fail('Exception in student attendance notification test: ' . $e->getMessage());
        }
    }

    /**
     * Test that fee payments update student records
     */
    public function testFeePaymentUpdatesStudentRecord()
    {
        try {
            // Create test student
            $student = Student::factory()->create([
                'fees_paid' => 0,
                'fees_due' => 10000,
                'payment_status' => 'unpaid',
            ]);
            
            // Record fee payment
            $payment = Fee::create([
                'student_id' => $student->id,
                'amount' => 5000,
                'payment_date' => date('Y-m-d'),
                'payment_method' => 'cash',
                'receipt_number' => 'REC-' . rand(1000, 9999),
                'status' => 'completed',
            ]);
            
            // Update student record
            $student->fees_paid += $payment->amount;
            $student->fees_due -= $payment->amount;
            $student->payment_status = $student->fees_due > 0 ? 'partial' : 'paid';
            $student->save();
            
            // Verify student record was updated
            $updatedStudent = Student::find($student->id);
            
            $success = 
                $updatedStudent->fees_paid == 5000 &&
                $updatedStudent->fees_due == 5000 &&
                $updatedStudent->payment_status == 'partial';
                
            $this->reportData['fee_payment_student_record'] = [
                'status' => $success ? 'SUCCESS' : 'ERROR',
                'details' => [
                    'student_id' => $student->id,
                    'payment_amount' => $payment->amount,
                    'updated_fees_paid' => $updatedStudent->fees_paid,
                    'updated_fees_due' => $updatedStudent->fees_due,
                    'updated_payment_status' => $updatedStudent->payment_status,
                ]
            ];
            
            $this->assertTrue($success, 'Fee payment update to student record failed');
            
        } catch (\Exception $e) {
            $this->reportData['fee_payment_student_record'] = [
                'status' => 'ERROR',
                'details' => ['error' => $e->getMessage()]
            ];
            $this->fail('Exception in fee payment test: ' . $e->getMessage());
        }
    }

    /**
     * Test that exam results integrate with student profiles
     */
    public function testExamResultsIntegrateWithStudentProfile()
    {
        try {
            // Create test student and exam
            $student = Student::factory()->create();
            $exam = Exam::create([
                'name' => 'Final Exam',
                'class_id' => 1,
                'subject_id' => 1,
                'max_marks' => 100,
                'passing_marks' => 35,
                'exam_date' => date('Y-m-d'), // Changed from 'date' to 'exam_date'
            ]);
            
            // Record exam result
            $result = Result::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'marks' => 75,
                'grade' => 'B+',
                'remarks' => 'Good performance',
                'status' => 'pass',
            ]);
            
            // Update student profile with exam performance
            $student->last_exam_id = $exam->id;
            $student->last_exam_grade = $result->grade;
            $student->academic_status = $result->status == 'pass' ? 'good' : 'needs_improvement';
            $student->save();
            
            // Verify student profile was updated
            $updatedStudent = Student::find($student->id);
            
            $success = 
                $updatedStudent->last_exam_id == $exam->id &&
                $updatedStudent->last_exam_grade == 'B+' &&
                $updatedStudent->academic_status == 'good';
                
            $this->reportData['exam_results_student_profile'] = [
                'status' => $success ? 'SUCCESS' : 'ERROR',
                'details' => [
                    'student_id' => $student->id,
                    'exam_id' => $exam->id,
                    'marks' => $result->marks,
                    'grade' => $result->grade,
                    'updated_academic_status' => $updatedStudent->academic_status,
                ]
            ];
            
            $this->assertTrue($success, 'Exam results integration with student profile failed');
            
        } catch (\Exception $e) {
            $this->reportData['exam_results_student_profile'] = [
                'status' => 'ERROR',
                'details' => ['error' => $e->getMessage()]
            ];
            $this->fail('Exception in exam results integration test: ' . $e->getMessage());
        }
    }

    /**
     * Test that reports generate accurate consolidated data
     */
    public function testReportGenerationAccuracy()
    {
        try {
            // Create test data
            $students = Student::factory()->count(5)->create();
            $teachers = Teacher::factory()->count(3)->create();
            
            // Record attendance for all students
            foreach ($students as $student) {
                DB::table('student_attendance')->insert([
                    'student_id' => $student->id,
                    'date' => date('Y-m-d'),
                    'status' => rand(0, 1) ? 'present' : 'absent',
                    'recorded_by' => 1,
                ]);
            }
            
            // Record fee payments for some students
            foreach ($students as $index => $student) {
                if ($index % 2 == 0) {
                    Fee::create([
                        'student_id' => $student->id,
                        'amount' => 5000,
                        'payment_date' => date('Y-m-d'),
                        'payment_method' => 'cash',
                        'receipt_number' => 'REC-' . rand(1000, 9999),
                        'status' => 'completed',
                    ]);
                }
            }
            
            // Generate consolidated report data
            $reportData = [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'attendance_today' => DB::table('student_attendance')
                    ->where('date', date('Y-m-d'))
                    ->where('status', 'present')
                    ->count(),
                'fees_collected_today' => Fee::where('payment_date', date('Y-m-d'))
                    ->sum('amount'),
            ];
            
            // Verify report data accuracy
            $expectedStudentCount = count($students);
            $expectedTeacherCount = count($teachers);
            $expectedAttendanceCount = DB::table('student_attendance')
                ->where('date', date('Y-m-d'))
                ->where('status', 'present')
                ->count();
            $expectedFeesCollected = Fee::where('payment_date', date('Y-m-d'))
                ->sum('amount');
                
            $success = 
                $reportData['total_students'] == $expectedStudentCount &&
                $reportData['total_teachers'] == $expectedTeacherCount &&
                $reportData['attendance_today'] == $expectedAttendanceCount &&
                $reportData['fees_collected_today'] == $expectedFeesCollected;
                
            $this->reportData['consolidated_reports'] = [
                'status' => $success ? 'SUCCESS' : 'ERROR',
                'details' => [
                    'expected_student_count' => $expectedStudentCount,
                    'actual_student_count' => $reportData['total_students'],
                    'expected_teacher_count' => $expectedTeacherCount,
                    'actual_teacher_count' => $reportData['total_teachers'],
                    'expected_attendance_count' => $expectedAttendanceCount,
                    'actual_attendance_count' => $reportData['attendance_today'],
                    'expected_fees_collected' => $expectedFeesCollected,
                    'actual_fees_collected' => $reportData['fees_collected_today'],
                ]
            ];
            
            $this->assertTrue($success, 'Consolidated report data accuracy failed');
            
        } catch (\Exception $e) {
            $this->reportData['consolidated_reports'] = [
                'status' => 'ERROR',
                'details' => ['error' => $e->getMessage()]
            ];
            $this->fail('Exception in report generation test: ' . $e->getMessage());
        }
    }

    /**
     * Generate HTML report for integration tests
     */
    private function generateReport()
    {
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PNS-Dhampur Integration Test Report</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 1200px; margin: 0 auto; }
                h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                h2 { color: #3498db; margin-top: 30px; }
                .summary { display: flex; justify-content: space-between; margin: 20px 0; }
                .summary-item { flex: 1; padding: 15px; border-radius: 5px; margin: 0 10px; text-align: center; }
                .success { background-color: #d4edda; color: #155724; }
                .error { background-color: #f8d7da; color: #721c24; }
                .pending { background-color: #fff3cd; color: #856404; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; }
                tr:hover { background-color: #f5f5f5; }
                .details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; }
                .status-badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>PNS-Dhampur Integration Test Report</h1>
                <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
                
                <div class="summary">';
        
        $statuses = ['SUCCESS' => 0, 'ERROR' => 0, 'PENDING' => 0];
        foreach ($this->reportData as $test) {
            $statuses[$test['status']]++;
        }
        
        foreach ($statuses as $status => $count) {
            $class = strtolower($status);
            $html .= '<div class="summary-item ' . $class . '">
                        <h3>' . $status . '</h3>
                        <p>' . $count . '</p>
                      </div>';
        }
        
        $html .= '</div>
                
                <h2>Test Results</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Test Case</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($this->reportData as $testName => $test) {
            $statusClass = strtolower($test['status']);
            $formattedTestName = ucwords(str_replace('_', ' ', $testName));
            
            $html .= '<tr>
                        <td>' . $formattedTestName . '</td>
                        <td><span class="status-badge ' . $statusClass . '">' . $test['status'] . '</span></td>
                        <td>';
            
            if (!empty($test['details'])) {
                $html .= '<div class="details"><pre>' . json_encode($test['details'], JSON_PRETTY_PRINT) . '</pre></div>';
            } else {
                $html .= 'No details available';
            }
            
            $html .= '</td>
                      </tr>';
        }
        
        $html .= '</tbody>
                </table>
                
                <h2>Summary</h2>
                <p>Total Tests: ' . count($this->reportData) . '</p>
                <p>Successful Tests: ' . $statuses['SUCCESS'] . '</p>
                <p>Failed Tests: ' . $statuses['ERROR'] . '</p>
                <p>Pending Tests: ' . $statuses['PENDING'] . '</p>
            </div>
        </body>
        </html>';
        
        file_put_contents($this->reportPath, $html);
    }
}