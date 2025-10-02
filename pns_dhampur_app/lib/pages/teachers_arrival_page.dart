import 'package:flutter/material.dart';

class TeachersArrivalPage extends StatefulWidget {
  final String token;
  const TeachersArrivalPage({Key? key, required this.token}) : super(key: key);

  @override
  _TeachersArrivalPageState createState() => _TeachersArrivalPageState();
}

class _TeachersArrivalPageState extends State<TeachersArrivalPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedDate = DateTime.now().toString().split(' ')[0];
  String _selectedDepartment = 'All Departments';

  // Sample data for teacher arrivals
  final List<Map<String, dynamic>> _teacherArrivals = [
    {
      'id': 'T001',
      'name': 'Dr. Rajesh Sharma',
      'department': 'Mathematics',
      'arrivalTime': '08:15 AM',
      'status': 'On Time',
      'biometricId': 'BIO001',
      'date': '2024-01-15',
      'location': 'Main Gate',
      'deviceId': 'DEV001',
      'photo': 'assets/teacher1.jpg',
    },
    {
      'id': 'T002',
      'name': 'Ms. Priya Singh',
      'department': 'English',
      'arrivalTime': '08:45 AM',
      'status': 'Late',
      'biometricId': 'BIO002',
      'date': '2024-01-15',
      'location': 'Side Gate',
      'deviceId': 'DEV002',
      'photo': 'assets/teacher2.jpg',
    },
    {
      'id': 'T003',
      'name': 'Mr. Amit Kumar',
      'department': 'Science',
      'arrivalTime': '08:00 AM',
      'status': 'Early',
      'biometricId': 'BIO003',
      'date': '2024-01-15',
      'location': 'Main Gate',
      'deviceId': 'DEV001',
      'photo': 'assets/teacher3.jpg',
    },
    {
      'id': 'T004',
      'name': 'Dr. Sunita Verma',
      'department': 'Social Studies',
      'arrivalTime': '09:15 AM',
      'status': 'Late',
      'biometricId': 'BIO004',
      'date': '2024-01-15',
      'location': 'Main Gate',
      'deviceId': 'DEV001',
      'photo': 'assets/teacher4.jpg',
    },
    {
      'id': 'T005',
      'name': 'Mr. Vikash Gupta',
      'department': 'Physical Education',
      'arrivalTime': '07:45 AM',
      'status': 'Early',
      'biometricId': 'BIO005',
      'date': '2024-01-15',
      'location': 'Sports Complex',
      'deviceId': 'DEV003',
      'photo': 'assets/teacher5.jpg',
    },
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Teachers Arrival Tracking',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF4CAF50),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Today', icon: Icon(Icons.today)),
            Tab(text: 'History', icon: Icon(Icons.history)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics)),
            Tab(text: 'Settings', icon: Icon(Icons.settings)),
          ],
        ),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF4CAF50), Color(0xFF388E3C)],
          ),
        ),
        child: TabBarView(
          controller: _tabController,
          children: [
            _buildTodayTab(),
            _buildHistoryTab(),
            _buildReportsTab(),
            _buildSettingsTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildTodayTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Header with stats
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Column(
              children: [
                const Text(
                  '🕐 Today\'s Arrival Status',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF4CAF50),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Real-time biometric attendance tracking! 👥✨',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[600],
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildStatCard('Present', '${_getTodayPresent()}', Icons.check_circle, const Color(0xFF4CAF50)),
                    _buildStatCard('Late', '${_getTodayLate()}', Icons.schedule, const Color(0xFFFF9800)),
                    _buildStatCard('Absent', '${_getTodayAbsent()}', Icons.cancel, const Color(0xFFF44336)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
          // Live arrivals
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(15),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: const BoxDecoration(
                      color: Color(0xFF4CAF50),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.access_time, color: Colors.white),
                        const SizedBox(width: 10),
                        const Text(
                          'Live Arrivals',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Text(
                            'LIVE',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _teacherArrivals.length,
                      itemBuilder: (context, index) {
                        final arrival = _teacherArrivals[index];
                        return _buildArrivalItem(arrival);
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHistoryTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Date and filter selection
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Column(
              children: [
                const Text(
                  '📅 Attendance History',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF4CAF50),
                  ),
                ),
                const SizedBox(height: 15),
                Row(
                  children: [
                    Expanded(
                      child: _buildDatePicker(),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: _buildDepartmentFilter(),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
          // History list
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(15),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: const BoxDecoration(
                      color: Color(0xFF4CAF50),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.history, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Arrival History',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _getFilteredArrivals().length,
                      itemBuilder: (context, index) {
                        final arrival = _getFilteredArrivals()[index];
                        return _buildHistoryItem(arrival);
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReportsTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: const Column(
              children: [
                Text(
                  '📊 Arrival Reports',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF4CAF50),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Comprehensive attendance analytics! 📈',
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
          Expanded(
            child: GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 15,
              mainAxisSpacing: 15,
              children: [
                _buildReportCard('Daily Summary', 'Today\'s attendance overview', Icons.today, const Color(0xFF4CAF50)),
                _buildReportCard('Weekly Report', 'Last 7 days analysis', Icons.date_range, const Color(0xFF2196F3)),
                _buildReportCard('Monthly Stats', 'Monthly attendance trends', Icons.calendar_month, const Color(0xFFFF9800)),
                _buildReportCard('Department Wise', 'Attendance by department', Icons.group, const Color(0xFF9C27B0)),
                _buildReportCard('Late Arrivals', 'Punctuality analysis', Icons.schedule, const Color(0xFFF44336)),
                _buildReportCard('Export Data', 'Download attendance reports', Icons.download, const Color(0xFF607D8B)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSettingsTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: const Column(
              children: [
                Text(
                  '⚙️ Biometric Settings',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF4CAF50),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Configure attendance system settings! 🔧',
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
          Expanded(
            child: ListView(
              children: [
                _buildSettingCard('Device Management', 'Configure biometric devices', Icons.fingerprint, () => _manageDevices()),
                const SizedBox(height: 15),
                _buildSettingCard('Time Settings', 'Set arrival time limits', Icons.access_time, () => _configureTime()),
                const SizedBox(height: 15),
                _buildSettingCard('Notifications', 'Alert preferences', Icons.notifications, () => _configureNotifications()),
                const SizedBox(height: 15),
                _buildSettingCard('Data Backup', 'Backup attendance data', Icons.backup, () => _backupData()),
                const SizedBox(height: 15),
                _buildSettingCard('User Permissions', 'Manage access rights', Icons.security, () => _managePermissions()),
                const SizedBox(height: 15),
                _buildSettingCard('System Status', 'Check device connectivity', Icons.wifi, () => _checkSystemStatus()),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 30),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              color: color,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildArrivalItem(Map<String, dynamic> arrival) {
    Color statusColor = _getStatusColor(arrival['status']);
    
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
        border: Border.left(color: statusColor, width: 4),
      ),
      child: Row(
        children: [
          // Profile picture placeholder
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: statusColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.person,
              color: statusColor,
              size: 30,
            ),
          ),
          const SizedBox(width: 15),
          
          // Teacher info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  arrival['name'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF4CAF50),
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  arrival['department'],
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 5),
                Row(
                  children: [
                    Icon(Icons.location_on, size: 14, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      arrival['location'],
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          
          // Status and time
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  arrival['status'],
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(height: 5),
              Text(
                arrival['arrivalTime'],
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF4CAF50),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildHistoryItem(Map<String, dynamic> arrival) {
    Color statusColor = _getStatusColor(arrival['status']);
    
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.left(color: statusColor, width: 3),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  arrival['name'],
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  '${arrival['department']} • ${arrival['location']}',
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                arrival['arrivalTime'],
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  arrival['status'],
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDatePicker() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Select Date',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF4CAF50),
          ),
        ),
        const SizedBox(height: 5),
        GestureDetector(
          onTap: () => _selectDate(),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                const Icon(Icons.calendar_today, size: 16),
                const SizedBox(width: 8),
                Text(_selectedDate),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDepartmentFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Department',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF4CAF50),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          value: _selectedDepartment,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          items: _getDepartments().map((String dept) {
            return DropdownMenuItem<String>(
              value: dept,
              child: Text(dept, style: const TextStyle(fontSize: 14)),
            );
          }).toList(),
          onChanged: (value) => setState(() => _selectedDepartment = value!),
        ),
      ],
    );
  }

  Widget _buildReportCard(String title, String description, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 30),
          ),
          const SizedBox(height: 15),
          Text(
            title,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            description,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildSettingCard(String title, String description, IconData icon, VoidCallback onTap) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: const Color(0xFF4CAF50).withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: const Color(0xFF4CAF50)),
        ),
        title: Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF4CAF50),
          ),
        ),
        subtitle: Text(description),
        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: onTap,
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'On Time':
      case 'Early':
        return const Color(0xFF4CAF50);
      case 'Late':
        return const Color(0xFFFF9800);
      case 'Absent':
        return const Color(0xFFF44336);
      default:
        return Colors.grey;
    }
  }

  List<String> _getDepartments() {
    return ['All Departments', ...{..._teacherArrivals.map((t) => t['department'] as String)}];
  }

  List<Map<String, dynamic>> _getFilteredArrivals() {
    return _teacherArrivals.where((arrival) {
      bool departmentMatch = _selectedDepartment == 'All Departments' || arrival['department'] == _selectedDepartment;
      bool dateMatch = arrival['date'] == _selectedDate;
      return departmentMatch && dateMatch;
    }).toList();
  }

  int _getTodayPresent() {
    return _teacherArrivals.where((t) => t['status'] != 'Absent').length;
  }

  int _getTodayLate() {
    return _teacherArrivals.where((t) => t['status'] == 'Late').length;
  }

  int _getTodayAbsent() {
    return 2; // Sample absent count
  }

  void _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.parse(_selectedDate),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() {
        _selectedDate = picked.toString().split(' ')[0];
      });
    }
  }

  void _manageDevices() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening device management...')),
    );
  }

  void _configureTime() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening time configuration...')),
    );
  }

  void _configureNotifications() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening notification settings...')),
    );
  }

  void _backupData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Starting data backup...')),
    );
  }

  void _managePermissions() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening permission management...')),
    );
  }

  void _checkSystemStatus() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Checking system status...')),
    );
  }
}