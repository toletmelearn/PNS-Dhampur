<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Academic Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
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
            color: #007bff;
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
            color: #007bff;
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
            color: #007bff;
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
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
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
        <div class="report-title">Academic Report</div>
        <div class="report-date">Generated on: {{ date('F j, Y') }}</div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Academic Overview</div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $data['total_students'] ?? 0 }}</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['total_teachers'] ?? 0 }}</div>
                <div class="stat-label">Total Teachers</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ count($data['enrollment']['classes'] ?? []) }}</div>
                <div class="stat-label">Total Classes</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['total_subjects'] ?? 0 }}</div>
                <div class="stat-label">Total Subjects</div>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="section-title">Class-wise Enrollment</div>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Total Students</th>
                    <th>Male</th>
                    <th>Female</th>
                    <th>Subjects</th>
                    <th>Class Teacher</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data['enrollment']['classes']))
                    @foreach($data['enrollment']['classes'] as $class)
                        <tr>
                            <td>{{ $class['name'] }}</td>
                            <td>{{ $class['total'] }}</td>
                            <td>{{ $class['male'] ?? 0 }}</td>
                            <td>{{ $class['female'] ?? 0 }}</td>
                            <td>{{ $class['subjects'] ?? 0 }}</td>
                            <td>{{ $class['teacher'] ?? 'Not Assigned' }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if(isset($data['subject_distribution']))
    <div class="table-section">
        <div class="section-title">Subject Distribution</div>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Classes</th>
                    <th>Total Students</th>
                    <th>Assigned Teachers</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['subject_distribution'] as $subject => $info)
                    <tr>
                        <td>{{ $subject }}</td>
                        <td>{{ $info['classes'] ?? 0 }}</td>
                        <td>{{ $info['students'] ?? 0 }}</td>
                        <td>{{ $info['teachers'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report was automatically generated by the PNS Dhampur School Management System.</p>
        <p>For any queries, please contact the administration office.</p>
    </div>
</body>
</html>