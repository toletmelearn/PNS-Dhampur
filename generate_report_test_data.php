<?php

/**
 * Test Data Generator for Report Generation Performance Testing
 * 
 * This script generates large datasets for performance testing of the report generation functionality
 * to ensure it can handle the requirement of 1,000 report generations per day.
 */

// Configuration
$config = [
    'num_classes' => 20,
    'num_sections' => 4,
    'num_students_per_class' => 40,
    'num_report_types' => 5,
    'date_ranges' => 10,
    'output_file' => 'report_parameters_large.csv',
    'admin_output_file' => 'admin_credentials_large.csv',
];

// Report types
$reportTypes = [
    'attendance',
    'academic',
    'financial',
    'exam',
    'behavior'
];

// Generate date ranges (for the past year)
$dateRanges = [];
$startDate = new DateTime('now');
$startDate->modify('-1 year');

for ($i = 0; $i < $config['date_ranges']; $i++) {
    $endDate = clone $startDate;
    $endDate->modify('+1 month');
    
    $dateRanges[] = [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d')
    ];
    
    $startDate->modify('+1 month');
}

// Generate admin credentials
$adminEmails = [
    'admin@pnsdhampur.edu',
    'principal@pnsdhampur.edu',
    'reports_admin@pnsdhampur.edu',
    'supervisor@pnsdhampur.edu',
    'director@pnsdhampur.edu'
];

$adminPasswords = [
    'admin123',
    'principal123',
    'reports123',
    'supervisor123',
    'director123'
];

// Open files for writing
$reportParamsFile = fopen($config['output_file'], 'w');
$adminCredsFile = fopen($config['admin_output_file'], 'w');

// Write headers
fputcsv($reportParamsFile, ['report_type', 'class_id', 'section_id', 'start_date', 'end_date']);
fputcsv($adminCredsFile, ['admin_email', 'admin_password']);

// Write admin credentials
foreach ($adminEmails as $index => $email) {
    fputcsv($adminCredsFile, [$email, $adminPasswords[$index]]);
}

// Generate report parameters to support 1,000 reports per day
$totalReports = 0;
$targetReports = 1000;

// Generate combinations of parameters
for ($class = 1; $class <= $config['num_classes']; $class++) {
    for ($section = 1; $section <= $config['num_sections']; $section++) {
        foreach ($reportTypes as $reportType) {
            foreach ($dateRanges as $dateRange) {
                fputcsv($reportParamsFile, [
                    $reportType,
                    $class,
                    $section,
                    $dateRange['start'],
                    $dateRange['end']
                ]);
                
                $totalReports++;
                
                // Stop when we have enough data to support our target
                if ($totalReports >= $targetReports) {
                    break 4;
                }
            }
        }
    }
}

// Close files
fclose($reportParamsFile);
fclose($adminCredsFile);

echo "Generated test data for {$totalReports} report combinations.\n";
echo "Files created:\n";
echo "- {$config['output_file']}\n";
echo "- {$config['admin_output_file']}\n";