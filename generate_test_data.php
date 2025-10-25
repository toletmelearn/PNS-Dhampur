<?php
/**
 * Test Data Generator for Performance Testing
 * 
 * This script generates realistic test data for performance testing:
 * - Large set of student attendance records
 * - Teacher credentials with class assignments
 * - Batch attendance submissions
 */

// Configuration
$numTeachers = 20;
$numClasses = 10;
$numSections = 3;
$studentsPerClass = 40;
$attendanceDays = 30;

// Output files
$teacherFile = 'teacher_credentials_large.csv';
$studentFile = 'student_data_large.csv';
$attendanceFile = 'attendance_data_large.csv';
$batchFile = 'batch_attendance_data.csv';

// Status options
$statusOptions = ['present', 'absent', 'late', 'half_day'];
$statusWeights = [70, 15, 10, 5]; // Probability weights

// Remarks for different statuses
$remarks = [
    'present' => ['', '', '', 'Good participation', 'Active in class'],
    'absent' => ['Sick leave', 'Family emergency', 'Medical appointment', 'Excused absence', 'Unexcused absence'],
    'late' => ['Arrived 10 minutes late', 'Bus delay', 'Traffic issues', 'Arrived 5 minutes late', ''],
    'half_day' => ['Left early for appointment', 'Arrived late due to doctor visit', 'Family obligation', '', '']
];

// Generate teacher data
echo "Generating teacher data...\n";
$teacherFh = fopen($teacherFile, 'w');
fputcsv($teacherFh, ['teacher_email', 'teacher_password', 'class_id', 'section_id']);

for ($i = 1; $i <= $numTeachers; $i++) {
    $classId = rand(1, $numClasses);
    $sectionId = rand(1, $numSections);
    $email = "teacher{$i}@pnsdhampur.edu";
    $password = "password" . str_pad($i, 3, '0', STR_PAD_LEFT);
    
    fputcsv($teacherFh, [$email, $password, $classId, $sectionId]);
}
fclose($teacherFh);

// Generate student data
echo "Generating student data...\n";
$studentFh = fopen($studentFile, 'w');
fputcsv($studentFh, ['student_id', 'name', 'class_id', 'section_id', 'roll_number']);

$studentId = 1;
for ($class = 1; $class <= $numClasses; $class++) {
    for ($section = 1; $section <= $numSections; $section++) {
        for ($roll = 1; $roll <= $studentsPerClass; $roll++) {
            $name = "Student " . str_pad($studentId, 4, '0', STR_PAD_LEFT);
            fputcsv($studentFh, [$studentId, $name, $class, $section, $roll]);
            $studentId++;
        }
    }
}
fclose($studentFh);

// Generate attendance data
echo "Generating attendance data...\n";
$attendanceFh = fopen($attendanceFile, 'w');
fputcsv($attendanceFh, ['student_id', 'status', 'date', 'remarks']);

// Generate dates for the last month
$dates = [];
$date = new DateTime();
for ($i = 0; $i < $attendanceDays; $i++) {
    $date->modify('-1 day');
    if ($date->format('N') <= 5) { // Weekdays only (1-5)
        $dates[] = $date->format('Y-m-d');
    }
}

// Generate random attendance for each student
$totalStudents = $numClasses * $numSections * $studentsPerClass;
for ($studentId = 1; $studentId <= $totalStudents; $studentId++) {
    foreach ($dates as $date) {
        // Weighted random status selection
        $rand = mt_rand(1, array_sum($statusWeights));
        $status = $statusOptions[0]; // Default to present
        
        $cumulativeWeight = 0;
        for ($i = 0; $i < count($statusOptions); $i++) {
            $cumulativeWeight += $statusWeights[$i];
            if ($rand <= $cumulativeWeight) {
                $status = $statusOptions[$i];
                break;
            }
        }
        
        // Get a random remark for this status
        $remarkOptions = $remarks[$status];
        $remark = $remarkOptions[array_rand($remarkOptions)];
        
        fputcsv($attendanceFh, [$studentId, $status, $date, $remark]);
    }
}
fclose($attendanceFh);

// Generate batch attendance data (for a single class/section on a specific day)
echo "Generating batch attendance data...\n";
$batchFh = fopen($batchFile, 'w');
fputcsv($batchFh, ['student_id', 'status', 'date', 'remarks', 'class_id', 'section_id']);

$batchDate = (new DateTime())->format('Y-m-d');
$batchClassId = rand(1, $numClasses);
$batchSectionId = rand(1, $numSections);

// Calculate student IDs for this class/section
$startId = (($batchClassId - 1) * $numSections * $studentsPerClass) + 
           (($batchSectionId - 1) * $studentsPerClass) + 1;
$endId = $startId + $studentsPerClass - 1;

for ($studentId = $startId; $studentId <= $endId; $studentId++) {
    // Weighted random status selection
    $rand = mt_rand(1, array_sum($statusWeights));
    $status = $statusOptions[0]; // Default to present
    
    $cumulativeWeight = 0;
    for ($i = 0; $i < count($statusOptions); $i++) {
        $cumulativeWeight += $statusWeights[$i];
        if ($rand <= $cumulativeWeight) {
            $status = $statusOptions[$i];
            break;
        }
    }
    
    // Get a random remark for this status
    $remarkOptions = $remarks[$status];
    $remark = $remarkOptions[array_rand($remarkOptions)];
    
    fputcsv($batchFh, [$studentId, $status, $batchDate, $remark, $batchClassId, $batchSectionId]);
}
fclose($batchFh);

echo "Test data generation complete!\n";
echo "Generated files:\n";
echo "- $teacherFile\n";
echo "- $studentFile\n";
echo "- $attendanceFile\n";
echo "- $batchFile\n";