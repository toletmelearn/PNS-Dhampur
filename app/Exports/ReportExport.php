<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $type;
    protected $data;

    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function array(): array
    {
        switch ($this->type) {
            case 'academic':
                return $this->formatAcademicData();
            case 'financial':
                return $this->formatFinancialData();
            case 'attendance':
                return $this->formatAttendanceData();
            case 'performance':
                return $this->formatPerformanceData();
            case 'administrative':
                return $this->formatAdministrativeData();
            default:
                return [];
        }
    }

    public function headings(): array
    {
        switch ($this->type) {
            case 'academic':
                return ['Class', 'Total Students', 'Male', 'Female', 'Subjects', 'Teachers'];
            case 'financial':
                return ['Month', 'Revenue', 'Expenses', 'Net Income', 'Collection Rate'];
            case 'attendance':
                return ['Date', 'Total Students', 'Present', 'Absent', 'Attendance Rate'];
            case 'performance':
                return ['Class', 'Subject', 'Average Score', 'Pass Rate', 'Top Scorer'];
            case 'administrative':
                return ['Department', 'Total Staff', 'Active', 'On Leave', 'Efficiency'];
            default:
                return [];
        }
    }

    public function title(): string
    {
        return ucfirst($this->type) . ' Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function formatAcademicData()
    {
        $formatted = [];
        foreach ($this->data['enrollment']['classes'] as $class) {
            $formatted[] = [
                $class['name'],
                $class['total'],
                $class['male'] ?? 0,
                $class['female'] ?? 0,
                $class['subjects'] ?? 0,
                $class['teachers'] ?? 0
            ];
        }
        return $formatted;
    }

    private function formatFinancialData()
    {
        $formatted = [];
        foreach ($this->data['monthly_collection'] as $month => $amount) {
            $expenses = $this->data['expenses'][$month] ?? 0;
            $formatted[] = [
                $month,
                $amount,
                $expenses,
                $amount - $expenses,
                '85%' // Mock collection rate
            ];
        }
        return $formatted;
    }

    private function formatAttendanceData()
    {
        $formatted = [];
        // Mock attendance data for export
        $dates = ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05'];
        foreach ($dates as $date) {
            $total = 500;
            $present = rand(400, 480);
            $absent = $total - $present;
            $rate = round(($present / $total) * 100, 1) . '%';
            
            $formatted[] = [$date, $total, $present, $absent, $rate];
        }
        return $formatted;
    }

    private function formatPerformanceData()
    {
        $formatted = [];
        foreach ($this->data['class_performance'] as $class => $subjects) {
            foreach ($subjects as $subject => $score) {
                $formatted[] = [
                    $class,
                    $subject,
                    $score,
                    rand(70, 95) . '%', // Mock pass rate
                    'Student ' . rand(1, 50) // Mock top scorer
                ];
            }
        }
        return $formatted;
    }

    private function formatAdministrativeData()
    {
        $departments = ['Academic', 'Administration', 'Finance', 'IT', 'Maintenance'];
        $formatted = [];
        
        foreach ($departments as $dept) {
            $total = rand(5, 20);
            $active = rand(3, $total);
            $onLeave = $total - $active;
            $efficiency = rand(80, 98) . '%';
            
            $formatted[] = [$dept, $total, $active, $onLeave, $efficiency];
        }
        
        return $formatted;
    }
}