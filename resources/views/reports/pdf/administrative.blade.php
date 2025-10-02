<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Administrative Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #fd7e14;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #fd7e14;
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
            color: #fd7e14;
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
            color: #fd7e14;
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
            color: #fd7e14;
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
            background-color: #fd7e14;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status {
            text-align: center;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .active {
            background-color: #d4edda;
            color: #155724;
        }
        .on-leave {
            background-color: #fff3cd;
            color: #856404;
        }
        .inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .efficiency {
            text-align: center;
            font-weight: bold;
        }
        .high-efficiency {
            color: #28a745;
        }
        .medium-efficiency {
            color: #ffc107;
        }
        .low-efficiency {
            color: #dc3545;
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
        <div class="report-title">Administrative Report</div>
        <div class="report-date">Generated on: {{ date('F j, Y') }}</div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Administrative Overview</div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $data['total_staff'] ?? 85 }}</div>
                <div class="stat-label">Total Staff</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['active_staff'] ?? 78 }}</div>
                <div class="stat-label">Active Staff</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['on_leave'] ?? 5 }}</div>
                <div class="stat-label">On Leave</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $data['efficiency'] ?? '92.3' }}%</div>
                <div class="stat-label">Overall Efficiency</div>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="section-title">Department-wise Staff Distribution</div>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Staff</th>
                    <th>Active</th>
                    <th>On Leave</th>
                    <th>Vacant Positions</th>
                    <th>Efficiency</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $departments = [
                        ['name' => 'Academic', 'total' => 45, 'active' => 42, 'leave' => 2, 'vacant' => 1, 'efficiency' => 94],
                        ['name' => 'Administration', 'total' => 15, 'active' => 14, 'leave' => 1, 'vacant' => 0, 'efficiency' => 96],
                        ['name' => 'Finance', 'total' => 8, 'active' => 7, 'leave' => 1, 'vacant' => 0, 'efficiency' => 92],
                        ['name' => 'IT Support', 'total' => 5, 'active' => 5, 'leave' => 0, 'vacant' => 0, 'efficiency' => 98],
                        ['name' => 'Maintenance', 'total' => 8, 'active' => 7, 'leave' => 1, 'vacant' => 0, 'efficiency' => 88],
                        ['name' => 'Security', 'total' => 6, 'active' => 6, 'leave' => 0, 'vacant' => 0, 'efficiency' => 95],
                    ];
                @endphp
                @foreach($departments as $dept)
                    @php
                        $efficiencyClass = $dept['efficiency'] >= 95 ? 'high-efficiency' : ($dept['efficiency'] >= 85 ? 'medium-efficiency' : 'low-efficiency');
                    @endphp
                    <tr>
                        <td>{{ $dept['name'] }}</td>
                        <td>{{ $dept['total'] }}</td>
                        <td>{{ $dept['active'] }}</td>
                        <td>{{ $dept['leave'] }}</td>
                        <td>{{ $dept['vacant'] }}</td>
                        <td class="efficiency {{ $efficiencyClass }}">{{ $dept['efficiency'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Staff Status Overview</div>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Join Date</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $staffMembers = [
                        ['id' => 'EMP001', 'name' => 'Dr. Rajesh Kumar', 'dept' => 'Academic', 'position' => 'Principal', 'status' => 'Active', 'join' => '2015-06-01', 'performance' => 'Excellent'],
                        ['id' => 'EMP002', 'name' => 'Mrs. Priya Sharma', 'dept' => 'Academic', 'position' => 'Vice Principal', 'status' => 'Active', 'join' => '2017-04-15', 'performance' => 'Excellent'],
                        ['id' => 'EMP003', 'name' => 'Mr. Amit Singh', 'dept' => 'Academic', 'position' => 'Math Teacher', 'status' => 'Active', 'join' => '2018-07-01', 'performance' => 'Good'],
                        ['id' => 'EMP004', 'name' => 'Ms. Neha Gupta', 'dept' => 'Academic', 'position' => 'Science Teacher', 'status' => 'On Leave', 'join' => '2019-08-20', 'performance' => 'Good'],
                        ['id' => 'EMP005', 'name' => 'Mr. Vikash Yadav', 'dept' => 'Administration', 'position' => 'Office Manager', 'status' => 'Active', 'join' => '2016-03-10', 'performance' => 'Excellent'],
                        ['id' => 'EMP006', 'name' => 'Mrs. Sunita Devi', 'dept' => 'Finance', 'position' => 'Accountant', 'status' => 'Active', 'join' => '2020-01-15', 'performance' => 'Good'],
                        ['id' => 'EMP007', 'name' => 'Mr. Ravi Kumar', 'dept' => 'IT Support', 'position' => 'IT Administrator', 'status' => 'Active', 'join' => '2021-09-01', 'performance' => 'Excellent'],
                        ['id' => 'EMP008', 'name' => 'Mr. Suresh Chand', 'dept' => 'Maintenance', 'position' => 'Supervisor', 'status' => 'On Leave', 'join' => '2014-11-20', 'performance' => 'Average'],
                    ];
                @endphp
                @foreach($staffMembers as $staff)
                    <tr>
                        <td>{{ $staff['id'] }}</td>
                        <td>{{ $staff['name'] }}</td>
                        <td>{{ $staff['dept'] }}</td>
                        <td>{{ $staff['position'] }}</td>
                        <td>
                            <span class="status {{ $staff['status'] === 'Active' ? 'active' : ($staff['status'] === 'On Leave' ? 'on-leave' : 'inactive') }}">
                                {{ $staff['status'] }}
                            </span>
                        </td>
                        <td>{{ date('M j, Y', strtotime($staff['join'])) }}</td>
                        <td>{{ $staff['performance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Monthly Attendance Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Working Days</th>
                    <th>Total Present</th>
                    <th>Total Absent</th>
                    <th>Leave Taken</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = ['January', 'February', 'March', 'April', 'May', 'June'];
                @endphp
                @foreach($months as $month)
                    @php
                        $workingDays = rand(20, 25);
                        $totalStaff = 85;
                        $totalPossible = $workingDays * $totalStaff;
                        $totalPresent = rand($totalPossible * 0.85, $totalPossible * 0.95);
                        $totalAbsent = $totalPossible - $totalPresent;
                        $leaveTaken = rand(50, 150);
                        $attendanceRate = round(($totalPresent / $totalPossible) * 100, 1);
                        $rateClass = $attendanceRate >= 95 ? 'high-efficiency' : ($attendanceRate >= 85 ? 'medium-efficiency' : 'low-efficiency');
                    @endphp
                    <tr>
                        <td>{{ $month }}</td>
                        <td>{{ $workingDays }}</td>
                        <td>{{ $totalPresent }}</td>
                        <td>{{ $totalAbsent }}</td>
                        <td>{{ $leaveTaken }}</td>
                        <td class="efficiency {{ $rateClass }}">{{ $attendanceRate }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <div class="section-title">Resource Utilization</div>
        <table>
            <thead>
                <tr>
                    <th>Resource Type</th>
                    <th>Total Available</th>
                    <th>Currently Used</th>
                    <th>Utilization Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $resources = [
                        ['type' => 'Classrooms', 'total' => 25, 'used' => 23, 'rate' => 92],
                        ['type' => 'Computer Labs', 'total' => 3, 'used' => 2, 'rate' => 67],
                        ['type' => 'Science Labs', 'total' => 4, 'used' => 3, 'rate' => 75],
                        ['type' => 'Library Seats', 'total' => 100, 'used' => 85, 'rate' => 85],
                        ['type' => 'Sports Equipment', 'total' => 50, 'used' => 42, 'rate' => 84],
                        ['type' => 'Audio-Visual Rooms', 'total' => 2, 'used' => 1, 'rate' => 50],
                    ];
                @endphp
                @foreach($resources as $resource)
                    @php
                        $status = $resource['rate'] >= 80 ? 'Optimal' : ($resource['rate'] >= 60 ? 'Good' : 'Underutilized');
                        $statusClass = $resource['rate'] >= 80 ? 'high-efficiency' : ($resource['rate'] >= 60 ? 'medium-efficiency' : 'low-efficiency');
                    @endphp
                    <tr>
                        <td>{{ $resource['type'] }}</td>
                        <td>{{ $resource['total'] }}</td>
                        <td>{{ $resource['used'] }}</td>
                        <td class="efficiency {{ $statusClass }}">{{ $resource['rate'] }}%</td>
                        <td class="{{ $statusClass }}">{{ $status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This report was automatically generated by the PNS Dhampur School Management System.</p>
        <p>For any queries, please contact the administration office.</p>
    </div>
</body>
</html>