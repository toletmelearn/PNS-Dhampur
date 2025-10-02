import 'package:flutter/material.dart';

class StudentAttendancePage extends StatefulWidget {
  final String token;

  const StudentAttendancePage({Key? key, required this.token}) : super(key: key);

  @override
  State<StudentAttendancePage> createState() => _StudentAttendancePageState();
}

class _StudentAttendancePageState extends State<StudentAttendancePage> with TickerProviderStateMixin {
  late TabController _tabController;
  DateTime _selectedDate = DateTime.now();
  String _selectedClass = 'Class 1';
  String _selectedSection = 'A';

  // Sample student data
  final Map<String, List<Map<String, dynamic>>> _classStudents = {
    'Class 1': [
      {
        'id': 1,
        'name': 'Aarav Sharma',
        'rollNo': '001',
        'photo': '👦',
        'attendance': 'present',
        'parentPhone': '+91 9876543210',
        'totalPresent': 85,
        'totalAbsent': 15,
        'attendancePercentage': 85.0,
      },
      {
        'id': 2,
        'name': 'Priya Singh',
        'rollNo': '002',
        'photo': '👧',
        'attendance': 'present',
        'parentPhone': '+91 9876543211',
        'totalPresent': 92,
        'totalAbsent': 8,
        'attendancePercentage': 92.0,
      },
      {
        'id': 3,
        'name': 'Arjun Kumar',
        'rollNo': '003',
        'photo': '👦',
        'attendance': 'absent',
        'parentPhone': '+91 9876543212',
        'totalPresent': 78,
        'totalAbsent': 22,
        'attendancePercentage': 78.0,
      },
      {
        'id': 4,
        'name': 'Ananya Gupta',
        'rollNo': '004',
        'photo': '👧',
        'attendance': 'present',
        'parentPhone': '+91 9876543213',
        'totalPresent': 88,
        'totalAbsent': 12,
        'attendancePercentage': 88.0,
      },
      {
        'id': 5,
        'name': 'Rohan Patel',
        'rollNo': '005',
        'photo': '👦',
        'attendance': 'late',
        'parentPhone': '+91 9876543214',
        'totalPresent': 82,
        'totalAbsent': 18,
        'attendancePercentage': 82.0,
      },
    ],
    'Class 2': [
      {
        'id': 6,
        'name': 'Kavya Reddy',
        'rollNo': '001',
        'photo': '👧',
        'attendance': 'present',
        'parentPhone': '+91 9876543215',
        'totalPresent': 90,
        'totalAbsent': 10,
        'attendancePercentage': 90.0,
      },
      {
        'id': 7,
        'name': 'Vikram Joshi',
        'rollNo': '002',
        'photo': '👦',
        'attendance': 'present',
        'parentPhone': '+91 9876543216',
        'totalPresent': 87,
        'totalAbsent': 13,
        'attendancePercentage': 87.0,
      },
    ],
  };

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('✅', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Attendance',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF009688),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            onPressed: _generateAttendanceReport,
            icon: const Icon(Icons.assessment, color: Colors.white),
          ),
          IconButton(
            onPressed: _sendNotifications,
            icon: const Icon(Icons.notifications, color: Colors.white),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: '📝 Mark'),
            Tab(text: '📊 Reports'),
            Tab(text: '📈 Analytics'),
            Tab(text: '⚙️ Settings'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildMarkAttendanceTab(),
          _buildReportsTab(),
          _buildAnalyticsTab(),
          _buildSettingsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _saveAttendance,
        backgroundColor: const Color(0xFF009688),
        icon: const Icon(Icons.save, color: Colors.white),
        label: const Text(
          'Save Attendance',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Widget _buildMarkAttendanceTab() {
    final students = _classStudents[_selectedClass] ?? [];
    
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Date and Class Selector
          _buildDateClassSelector(),
          const SizedBox(height: 24),

          // Attendance Summary
          _buildAttendanceSummary(students),
          const SizedBox(height: 24),

          // Quick Actions
          _buildQuickActions(),
          const SizedBox(height: 24),

          // Student List
          Text(
            '👥 Students - $_selectedClass $_selectedSection',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 16),

          ...students.map((student) => _buildStudentAttendanceCard(student)).toList(),
        ],
      ),
    );
  }

  Widget _buildReportsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Report Filters
          _buildReportFilters(),
          const SizedBox(height: 24),

          // Class-wise Attendance Summary
          _buildClasswiseAttendance(),
          const SizedBox(height: 24),

          // Monthly Attendance Chart
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📊 Monthly Attendance Trends',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildMonthlyAttendanceChart(),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Low Attendance Students
          _buildLowAttendanceStudents(),
        ],
      ),
    );
  }

  Widget _buildAnalyticsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Overall Statistics
          Row(
            children: [
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Overall Attendance',
                  value: '86.5%',
                  icon: Icons.trending_up,
                  color: const Color(0xFF4CAF50),
                  emoji: '📈',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Present Today',
                  value: '142/165',
                  icon: Icons.check_circle,
                  color: const Color(0xFF2196F3),
                  emoji: '✅',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Absent Today',
                  value: '23',
                  icon: Icons.cancel,
                  color: const Color(0xFFFF5722),
                  emoji: '❌',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Late Arrivals',
                  value: '8',
                  icon: Icons.access_time,
                  color: const Color(0xFFFF9800),
                  emoji: '⏰',
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Day-wise Attendance Pattern
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📅 Day-wise Attendance Pattern',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildDayWisePattern(),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Class Performance Comparison
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '🏆 Class Performance Comparison',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildClassComparison(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSettingsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Attendance Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '⚙️ Attendance Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.schedule, color: Color(0xFF009688)),
                    title: const Text('Late Arrival Time'),
                    subtitle: const Text('Mark as late after 9:30 AM'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _setLateArrivalTime(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.warning, color: Color(0xFF009688)),
                    title: const Text('Low Attendance Alert'),
                    subtitle: const Text('Alert when attendance < 75%'),
                    trailing: Switch(
                      value: true,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF009688),
                    ),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.auto_awesome, color: Color(0xFF009688)),
                    title: const Text('Auto-mark Present'),
                    subtitle: const Text('Auto-mark students as present'),
                    trailing: Switch(
                      value: false,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF009688),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Notification Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '🔔 Notification Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.sms, color: Color(0xFF009688)),
                    title: const Text('SMS to Parents'),
                    subtitle: const Text('Send absence SMS to parents'),
                    trailing: Switch(
                      value: true,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF009688),
                    ),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.email, color: Color(0xFF009688)),
                    title: const Text('Email Reports'),
                    subtitle: const Text('Weekly attendance reports'),
                    trailing: Switch(
                      value: true,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF009688),
                    ),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.push_pin, color: Color(0xFF009688)),
                    title: const Text('Push Notifications'),
                    subtitle: const Text('Real-time attendance alerts'),
                    trailing: Switch(
                      value: false,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF009688),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Data Management
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '💾 Data Management',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.file_download, color: Color(0xFF009688)),
                    title: const Text('Export Attendance'),
                    subtitle: const Text('Download attendance as Excel'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _exportAttendance(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.backup, color: Color(0xFF009688)),
                    title: const Text('Backup Data'),
                    subtitle: const Text('Create attendance backup'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _backupAttendance(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.restore, color: Color(0xFF009688)),
                    title: const Text('Import Data'),
                    subtitle: const Text('Import attendance from file'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _importAttendance(),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDateClassSelector() {
    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFF009688), Color(0xFF00BCD4)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          children: [
            // Date Selector
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.calendar_today,
                    color: Colors.white,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Date',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      Text(
                        '${_selectedDate.day}/${_selectedDate.month}/${_selectedDate.year}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: _selectDate,
                  icon: const Icon(Icons.edit, color: Colors.white),
                ),
                const Text('📅', style: TextStyle(fontSize: 24)),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(color: Colors.white30),
            const SizedBox(height: 16),
            // Class Selector
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Class',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      DropdownButton<String>(
                        value: _selectedClass,
                        dropdownColor: const Color(0xFF009688),
                        style: const TextStyle(color: Colors.white, fontSize: 16),
                        underline: Container(),
                        items: _classStudents.keys.map((String value) {
                          return DropdownMenuItem<String>(
                            value: value,
                            child: Text(value),
                          );
                        }).toList(),
                        onChanged: (String? newValue) {
                          setState(() {
                            _selectedClass = newValue!;
                          });
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 20),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Section',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      DropdownButton<String>(
                        value: _selectedSection,
                        dropdownColor: const Color(0xFF009688),
                        style: const TextStyle(color: Colors.white, fontSize: 16),
                        underline: Container(),
                        items: ['A', 'B', 'C'].map((String value) {
                          return DropdownMenuItem<String>(
                            value: value,
                            child: Text(value),
                          );
                        }).toList(),
                        onChanged: (String? newValue) {
                          setState(() {
                            _selectedSection = newValue!;
                          });
                        },
                      ),
                    ],
                  ),
                ),
                const Text('🏫', style: TextStyle(fontSize: 24)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAttendanceSummary(List<Map<String, dynamic>> students) {
    final presentCount = students.where((s) => s['attendance'] == 'present').length;
    final absentCount = students.where((s) => s['attendance'] == 'absent').length;
    final lateCount = students.where((s) => s['attendance'] == 'late').length;
    final totalStudents = students.length;

    return Row(
      children: [
        Expanded(
          child: _buildSummaryCard(
            title: 'Present',
            value: '$presentCount',
            total: totalStudents,
            icon: Icons.check_circle,
            color: const Color(0xFF4CAF50),
            emoji: '✅',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Absent',
            value: '$absentCount',
            total: totalStudents,
            icon: Icons.cancel,
            color: const Color(0xFFFF5722),
            emoji: '❌',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Late',
            value: '$lateCount',
            total: totalStudents,
            icon: Icons.access_time,
            color: const Color(0xFFFF9800),
            emoji: '⏰',
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '⚡ Quick Actions',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _markAllPresent,
                    icon: const Icon(Icons.check_circle, color: Colors.white),
                    label: const Text('All Present', style: TextStyle(color: Colors.white)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF4CAF50),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _markAllAbsent,
                    icon: const Icon(Icons.cancel, color: Colors.white),
                    label: const Text('All Absent', style: TextStyle(color: Colors.white)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFF5722),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _resetAttendance,
                    icon: const Icon(Icons.refresh, color: Colors.white),
                    label: const Text('Reset', style: TextStyle(color: Colors.white)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF607D8B),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStudentAttendanceCard(Map<String, dynamic> student) {
    Color statusColor;
    IconData statusIcon;
    String statusText;

    switch (student['attendance']) {
      case 'present':
        statusColor = const Color(0xFF4CAF50);
        statusIcon = Icons.check_circle;
        statusText = 'Present';
        break;
      case 'absent':
        statusColor = const Color(0xFFFF5722);
        statusIcon = Icons.cancel;
        statusText = 'Absent';
        break;
      case 'late':
        statusColor = const Color(0xFFFF9800);
        statusIcon = Icons.access_time;
        statusText = 'Late';
        break;
      default:
        statusColor = Colors.grey;
        statusIcon = Icons.help;
        statusText = 'Unknown';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            // Student Photo and Info
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF009688).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                student['photo'],
                style: const TextStyle(fontSize: 24),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    student['name'],
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'Roll No: ${student['rollNo']}',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${student['attendancePercentage']}% attendance',
                    style: TextStyle(
                      color: student['attendancePercentage'] >= 75 ? Colors.green : Colors.red,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
            // Attendance Status Buttons
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildAttendanceButton(
                  student,
                  'present',
                  Icons.check_circle,
                  const Color(0xFF4CAF50),
                  '✅',
                ),
                const SizedBox(width: 8),
                _buildAttendanceButton(
                  student,
                  'absent',
                  Icons.cancel,
                  const Color(0xFFFF5722),
                  '❌',
                ),
                const SizedBox(width: 8),
                _buildAttendanceButton(
                  student,
                  'late',
                  Icons.access_time,
                  const Color(0xFFFF9800),
                  '⏰',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAttendanceButton(
    Map<String, dynamic> student,
    String status,
    IconData icon,
    Color color,
    String emoji,
  ) {
    final isSelected = student['attendance'] == status;
    
    return GestureDetector(
      onTap: () {
        setState(() {
          student['attendance'] = status;
        });
      },
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: isSelected ? color : color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: color,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Text(
          emoji,
          style: const TextStyle(fontSize: 16),
        ),
      ),
    );
  }

  Widget _buildSummaryCard({
    required String title,
    required String value,
    required int total,
    required IconData icon,
    required Color color,
    required String emoji,
  }) {
    final percentage = total > 0 ? (int.parse(value) / total * 100).round() : 0;
    
    return Card(
      elevation: 8,
      shadowColor: color.withOpacity(0.3),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [color, color.withOpacity(0.8)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, color: Colors.white, size: 24),
                Text(emoji, style: const TextStyle(fontSize: 24)),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              '$value/$total',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              '$title ($percentage%)',
              style: TextStyle(
                color: Colors.white.withOpacity(0.9),
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAnalyticsCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
    required String emoji,
  }) {
    return Card(
      elevation: 8,
      shadowColor: color.withOpacity(0.3),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [color, color.withOpacity(0.8)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, color: Colors.white, size: 24),
                Text(emoji, style: const TextStyle(fontSize: 24)),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: TextStyle(
                color: Colors.white.withOpacity(0.9),
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReportFilters() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '🔍 Report Filters',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: 'This Month',
                    decoration: const InputDecoration(
                      labelText: 'Time Period',
                      border: OutlineInputBorder(),
                    ),
                    items: ['This Week', 'This Month', 'This Quarter', 'This Year']
                        .map((String value) {
                      return DropdownMenuItem<String>(
                        value: value,
                        child: Text(value),
                      );
                    }).toList(),
                    onChanged: (String? newValue) {},
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: 'All Classes',
                    decoration: const InputDecoration(
                      labelText: 'Class Filter',
                      border: OutlineInputBorder(),
                    ),
                    items: ['All Classes', 'Class 1', 'Class 2', 'Class 3']
                        .map((String value) {
                      return DropdownMenuItem<String>(
                        value: value,
                        child: Text(value),
                      );
                    }).toList(),
                    onChanged: (String? newValue) {},
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildClasswiseAttendance() {
    final classData = [
      {'class': 'Class 1', 'attendance': 86.5, 'students': 25},
      {'class': 'Class 2', 'attendance': 88.2, 'students': 28},
      {'class': 'Class 3', 'attendance': 84.7, 'students': 30},
      {'class': 'Class 4', 'attendance': 90.1, 'students': 27},
      {'class': 'Class 5', 'attendance': 87.3, 'students': 26},
    ];

    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '🏫 Class-wise Attendance',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF333333),
              ),
            ),
            const SizedBox(height: 20),
            ...classData.map((data) => Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        data['class'] as String,
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(
                        '${data['attendance']}% (${data['students']} students)',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  LinearProgressIndicator(
                    value: (data['attendance'] as double) / 100,
                    backgroundColor: Colors.grey[200],
                    valueColor: AlwaysStoppedAnimation<Color>(
                      (data['attendance'] as double) >= 85 
                          ? const Color(0xFF4CAF50) 
                          : const Color(0xFFFF9800),
                    ),
                    minHeight: 8,
                  ),
                ],
              ),
            )).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildMonthlyAttendanceChart() {
    final monthlyData = [
      {'month': 'Jan', 'attendance': 85.2},
      {'month': 'Feb', 'attendance': 87.1},
      {'month': 'Mar', 'attendance': 86.5},
      {'month': 'Apr', 'attendance': 88.3},
      {'month': 'May', 'attendance': 84.7},
    ];

    return Column(
      children: monthlyData.map((data) {
        final percentage = data['attendance'] as double;
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    data['month'] as String,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    '${percentage}%',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: percentage / 100,
                backgroundColor: Colors.grey[200],
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF009688)),
                minHeight: 8,
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildLowAttendanceStudents() {
    final lowAttendanceStudents = [
      {'name': 'Arjun Kumar', 'class': 'Class 1', 'attendance': 78.0},
      {'name': 'Rohan Patel', 'class': 'Class 1', 'attendance': 82.0},
      {'name': 'Sneha Sharma', 'class': 'Class 2', 'attendance': 74.5},
    ];

    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '⚠️ Low Attendance Alert',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFFFF5722),
              ),
            ),
            const SizedBox(height: 16),
            ...lowAttendanceStudents.map((student) => ListTile(
              leading: const CircleAvatar(
                backgroundColor: Color(0xFFFF5722),
                child: Icon(Icons.warning, color: Colors.white),
              ),
              title: Text(student['name'] as String),
              subtitle: Text('${student['class']} - ${student['attendance']}%'),
              trailing: IconButton(
                onPressed: () => _contactParent(student),
                icon: const Icon(Icons.phone, color: Color(0xFF009688)),
              ),
            )).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildDayWisePattern() {
    final dayData = [
      {'day': 'Monday', 'attendance': 88.5},
      {'day': 'Tuesday', 'attendance': 86.2},
      {'day': 'Wednesday', 'attendance': 87.8},
      {'day': 'Thursday', 'attendance': 85.1},
      {'day': 'Friday', 'attendance': 89.3},
      {'day': 'Saturday', 'attendance': 84.7},
    ];

    return Column(
      children: dayData.map((data) {
        final percentage = data['attendance'] as double;
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    data['day'] as String,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    '${percentage}%',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: percentage / 100,
                backgroundColor: Colors.grey[200],
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF009688)),
                minHeight: 8,
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildClassComparison() {
    final comparisonData = [
      {'class': 'Class 1', 'attendance': 86.5, 'rank': 3},
      {'class': 'Class 2', 'attendance': 88.2, 'rank': 2},
      {'class': 'Class 3', 'attendance': 84.7, 'rank': 5},
      {'class': 'Class 4', 'attendance': 90.1, 'rank': 1},
      {'class': 'Class 5', 'attendance': 87.3, 'rank': 4},
    ];

    return Column(
      children: comparisonData.map((data) {
        final percentage = data['attendance'] as double;
        final rank = data['rank'] as int;
        String rankEmoji;
        Color rankColor;

        switch (rank) {
          case 1:
            rankEmoji = '🥇';
            rankColor = const Color(0xFFFFD700);
            break;
          case 2:
            rankEmoji = '🥈';
            rankColor = const Color(0xFFC0C0C0);
            break;
          case 3:
            rankEmoji = '🥉';
            rankColor = const Color(0xFFCD7F32);
            break;
          default:
            rankEmoji = '📊';
            rankColor = Colors.grey;
        }

        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: rankColor.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(rankEmoji, style: const TextStyle(fontSize: 20)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          data['class'] as String,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        Text(
                          '${percentage}%',
                          style: TextStyle(color: Colors.grey[600]),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    LinearProgressIndicator(
                      value: percentage / 100,
                      backgroundColor: Colors.grey[200],
                      valueColor: AlwaysStoppedAnimation<Color>(rankColor),
                      minHeight: 6,
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  void _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  void _markAllPresent() {
    setState(() {
      final students = _classStudents[_selectedClass] ?? [];
      for (var student in students) {
        student['attendance'] = 'present';
      }
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('All students marked as present! ✅'),
        backgroundColor: Color(0xFF4CAF50),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _markAllAbsent() {
    setState(() {
      final students = _classStudents[_selectedClass] ?? [];
      for (var student in students) {
        student['attendance'] = 'absent';
      }
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('All students marked as absent! ❌'),
        backgroundColor: Color(0xFFFF5722),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _resetAttendance() {
    setState(() {
      final students = _classStudents[_selectedClass] ?? [];
      for (var student in students) {
        student['attendance'] = 'present'; // Default to present
      }
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Attendance reset! 🔄'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _saveAttendance() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('💾 Save Attendance'),
        content: Text('Save attendance for $_selectedClass $_selectedSection on ${_selectedDate.day}/${_selectedDate.month}/${_selectedDate.year}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Attendance saved successfully! 💾'),
                  backgroundColor: Color(0xFF009688),
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF009688)),
            child: const Text('Save', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _generateAttendanceReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Attendance report generation will be implemented! 📊'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _sendNotifications() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Sending notifications to parents! 📱'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _contactParent(Map<String, dynamic> student) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Contacting parent of ${student['name']}! 📞'),
        backgroundColor: const Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _setLateArrivalTime() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Late arrival time setting will be implemented! ⏰'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _exportAttendance() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Attendance export will be implemented! 📤'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _backupAttendance() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Attendance backup will be implemented! 💾'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _importAttendance() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Attendance import will be implemented! 📥'),
        backgroundColor: Color(0xFF009688),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}