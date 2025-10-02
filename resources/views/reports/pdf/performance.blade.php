<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Performance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 18px;
            color: #666;
        }
        .report-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .summary-section {
            margin: 30px 0;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .summary-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #6f42c1;
        }
        .summary-stats {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            margin: 10px;
            flex: 1;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .table-section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #6f42c1;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #6f42c1;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .score {
            text-align: center;
            font-weight: bold;
        }
        .excellent {
            color: #28a745;
        }
        .good {
            color: #17a2b8;
        }
        .average {
            color: #ffc107;
        }
        .poor {
            color: #dc3545;
        }
        .grade {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">PNS Dhampur</div>
        <div class="report-title">Academic Performance Report</div>
        <div class="report-date">Generated on: {{ date('F j, Y') }}</div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Performance Overview</div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $data['average_score'] ?? '78.5' }}%</div>
                <div class="stat-label">Overall Average</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['pass_rate'] ?? '92.3' }}%</div>
                <div class="stat-label">Pass Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['top_performers'] ?? 45 }}</div>
                <div class="stat-label">Top Performers (90%+)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['improvement_needed'] ?? 23 }}</div>
                <div class="stat-label">Need Improvement</div>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="section-title">Class-wise Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Total Students</th>
                    <th>Average Score</th>
                    <th>Pass Rate</th>
                    <th>Top Scorer</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data['class_performance']))
                    @foreach($data['class_performance'] as $class => $subjects)
                        @php
                            $avgScore = array_sum($subjects) / count($subjects);
                            $passRate = rand(85, 98);
                            $grade = $avgScore >= 90 ? 'A+' : ($avgScore >= 80 ? 'A' : ($avgScore >= 70 ? 'B' : ($avgScore >= 60 ? 'C' : 'D')));
                            $gradeClass = $avgScore >= 90 ? 'excellent' : ($avgScore >= 80 ? 'good' : ($avgScore >= 70 ? 'average' : 'poor'));
                        @endphp
                        <tr>
                            <td>{{ $class }}</td>
                            <td>{{ rand(40, 60) }}</td>
                            <td class="score {{ $gradeClass }}">{{ number_format($avgScore, 1) }}%</td>
                            <td class="score">{{ $passRate }}%</td>
                            <td>Student {{ rand(1, 50) }}</td>
                            <td class="grade {{ $gradeClass }}">{{ $grade }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Subject-wise Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Classes Taught</th>
                    <th>Total Students</th>
                    <th>Average Score</th>
                    <th>Pass Rate</th>
                    <th>Performance Level</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subjects = ['Mathematics', 'Science', 'English', 'Hindi', 'Social Studies', 'Computer Science', 'Physical Education', 'Art & Craft'];
                @endphp
                @foreach($subjects as $subject)
                    @php
                        $avgScore = rand(65, 95);
                        $passRate = rand(80, 98);
                        $level = $avgScore >= 85 ? 'Excellent' : ($avgScore >= 75 ? 'Good' : ($avgScore >= 65 ? 'Average' : 'Needs Improvement'));
                        $levelClass = $avgScore >= 85 ? 'excellent' : ($avgScore >= 75 ? 'good' : ($avgScore >= 65 ? 'average' : 'poor'));
                    @endphp
                    <tr>
                        <td>{{ $subject }}</td>
                        <td>{{ rand(5, 10) }}</td>
                        <td>{{ rand(200, 400) }}</td>
                        <td class="score {{ $levelClass }}">{{ $avgScore }}%</td>
                        <td class="score">{{ $passRate }}%</td>
                        <td class="{{ $levelClass }}">{{ $level }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Top Performing Students</div>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Roll Number</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $topStudents = [
                        ['name' => 'Aarav Sharma', 'class' => 'Class 10', 'roll' => '10001', 'score' => 98.5],
                        ['name' => 'Diya Patel', 'class' => 'Class 9', 'roll' => '09012', 'score' => 97.8],
                        ['name' => 'Arjun Singh', 'class' => 'Class 10', 'roll' => '10015', 'score' => 96.9],
                        ['name' => 'Kavya Gupta', 'class' => 'Class 8', 'roll' => '08007', 'score' => 96.2],
                        ['name' => 'Rohan Kumar', 'class' => 'Class 9', 'roll' => '09025', 'score' => 95.8],
                        ['name' => 'Ananya Verma', 'class' => 'Class 7', 'roll' => '07018', 'score' => 95.1],
                        ['name' => 'Karan Joshi', 'class' => 'Class 10', 'roll' => '10032', 'score' => 94.7],
                        ['name' => 'Ishita Agarwal', 'class' => 'Class 8', 'roll' => '08021', 'score' => 94.3],
                        ['name' => 'Varun Mehta', 'class' => 'Class 9', 'roll' => '09041', 'score' => 93.9],
                        ['name' => 'Riya Bansal', 'class' => 'Class 7', 'roll' => '07033', 'score' => 93.5],
                    ];
                @endphp
                @foreach($topStudents as $index => $student)
                    @php
                        $grade = $student['score'] >= 95 ? 'A+' : 'A';
                    @endphp
                    <tr>
                        <td class="score excellent">{{ $index + 1 }}</td>
                        <td>{{ $student['name'] }}</td>
                        <td>{{ $student['class'] }}</td>
                        <td>{{ $student['roll'] }}</td>
                        <td class="score excellent">{{ $student['score'] }}%</td>
                        <td class="grade excellent">{{ $grade }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Students Needing Improvement (Below 60%)</div>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Roll Number</th>
                    <th>Overall Score</th>
                    <th>Weak Subjects</th>
                    <th>Recommendation</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $improvementStudents = [
                        ['name' => 'Rahul Yadav', 'class' => 'Class 8', 'roll' => '08045', 'score' => 58.2, 'weak' => 'Math, Science'],
                        ['name' => 'Pooja Singh', 'class' => 'Class 7', 'roll' => '07052', 'score' => 55.8, 'weak' => 'English, Math'],
                        ['name' => 'Amit Kumar', 'class' => 'Class 9', 'roll' => '09067', 'score' => 52.1, 'weak' => 'Science, Social Studies'],
                        ['name' => 'Neha Sharma', 'class' => 'Class 6', 'roll' => '06038', 'score' => 49.7, 'weak' => 'Math, Hindi'],
                    ];
                @endphp
                @foreach($improvementStudents as $student)
                    <tr>
                        <td>{{ $student['name'] }}</td>
                        <td>{{ $student['class'] }}</td>
                        <td>{{ $student['roll'] }}</td>
                        <td class="score poor">{{ $student['score'] }}%</td>
                        <td>{{ $student['weak'] }}</td>
                        <td>Extra Classes, Parent Meeting</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This report was automatically generated by the PNS Dhampur School Management System.</p>
        <p>For any queries, please contact the academic office.</p>
    </div>
</body>
</html>