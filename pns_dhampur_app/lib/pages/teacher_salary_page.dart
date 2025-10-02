import 'package:flutter/material.dart';

class TeacherSalaryPage extends StatefulWidget {
  final String token;

  const TeacherSalaryPage({Key? key, required this.token}) : super(key: key);

  @override
  State<TeacherSalaryPage> createState() => _TeacherSalaryPageState();
}

class _TeacherSalaryPageState extends State<TeacherSalaryPage> with TickerProviderStateMixin {
  late TabController _tabController;
  String _selectedMonth = 'January 2024';
  String _selectedTeacher = 'All Teachers';

  // Sample teacher salary data
  final List<Map<String, dynamic>> _teacherSalaries = [
    {
      'id': 1,
      'name': 'Mrs. Priya Sharma',
      'designation': 'Principal',
      'basicSalary': 45000,
      'allowances': 8000,
      'deductions': 2000,
      'netSalary': 51000,
      'clTaken': 2,
      'mlTaken': 1,
      'clBalance': 10,
      'mlBalance': 11,
      'photo': '👩‍💼',
      'status': 'Paid',
      'paymentDate': '2024-01-31',
    },
    {
      'id': 2,
      'name': 'Mr. Rajesh Kumar',
      'designation': 'Math Teacher',
      'basicSalary': 35000,
      'allowances': 5000,
      'deductions': 1500,
      'netSalary': 38500,
      'clTaken': 1,
      'mlTaken': 0,
      'clBalance': 11,
      'mlBalance': 12,
      'photo': '👨‍🏫',
      'status': 'Paid',
      'paymentDate': '2024-01-31',
    },
    {
      'id': 3,
      'name': 'Ms. Sunita Devi',
      'designation': 'English Teacher',
      'basicSalary': 32000,
      'allowances': 4500,
      'deductions': 1200,
      'netSalary': 35300,
      'clTaken': 3,
      'mlTaken': 2,
      'clBalance': 9,
      'mlBalance': 10,
      'photo': '👩‍🏫',
      'status': 'Pending',
      'paymentDate': null,
    },
    {
      'id': 4,
      'name': 'Mr. Amit Singh',
      'designation': 'Science Teacher',
      'basicSalary': 33000,
      'allowances': 4800,
      'deductions': 1300,
      'netSalary': 36500,
      'clTaken': 0,
      'mlTaken': 1,
      'clBalance': 12,
      'mlBalance': 11,
      'photo': '👨‍🔬',
      'status': 'Paid',
      'paymentDate': '2024-01-31',
    },
  ];

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
              child: const Text('💵', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Teacher Salary',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF607D8B),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            onPressed: _showPayrollReport,
            icon: const Icon(Icons.assessment, color: Colors.white),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: '💰 Salaries'),
            Tab(text: '📅 Leave'),
            Tab(text: '📊 Reports'),
            Tab(text: '⚙️ Settings'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildSalariesTab(),
          _buildLeaveTab(),
          _buildReportsTab(),
          _buildSettingsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _processSalary,
        backgroundColor: const Color(0xFF607D8B),
        icon: const Icon(Icons.payment, color: Colors.white),
        label: const Text(
          'Process Salary',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Widget _buildSalariesTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Month Selector and Summary
          _buildMonthSelector(),
          const SizedBox(height: 24),
          
          // Summary Cards
          _buildSalarySummary(),
          const SizedBox(height: 24),

          // Teacher Filter
          _buildTeacherFilter(),
          const SizedBox(height: 24),

          // Teacher Salary List
          Text(
            '👥 Teacher Salaries',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 16),

          ..._teacherSalaries.map((teacher) => _buildTeacherSalaryCard(teacher)).toList(),
        ],
      ),
    );
  }

  Widget _buildLeaveTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Leave Summary
          _buildLeaveSummary(),
          const SizedBox(height: 24),

          // Leave Balance Cards
          Text(
            '📊 Leave Balance Overview',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 16),

          ..._teacherSalaries.map((teacher) => _buildLeaveBalanceCard(teacher)).toList(),
        ],
      ),
    );
  }

  Widget _buildReportsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Report Summary Cards
          Row(
            children: [
              Expanded(
                child: _buildReportCard(
                  title: 'Total Payroll',
                  value: '₹1,61,300',
                  icon: Icons.account_balance_wallet,
                  color: const Color(0xFF4CAF50),
                  emoji: '💰',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildReportCard(
                  title: 'Avg Salary',
                  value: '₹40,325',
                  icon: Icons.trending_up,
                  color: const Color(0xFF2196F3),
                  emoji: '📈',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildReportCard(
                  title: 'Total CL Used',
                  value: '6 days',
                  icon: Icons.event_busy,
                  color: const Color(0xFFFF5722),
                  emoji: '📅',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildReportCard(
                  title: 'Total ML Used',
                  value: '4 days',
                  icon: Icons.local_hospital,
                  color: const Color(0xFF9C27B0),
                  emoji: '🏥',
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Monthly Salary Chart
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📊 Monthly Salary Distribution',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildSalaryDistributionChart(),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Leave Usage Chart
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📈 Leave Usage Trends',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildLeaveUsageChart(),
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
          // Salary Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '💰 Salary Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.edit, color: Color(0xFF607D8B)),
                    title: const Text('Edit Salary Structure'),
                    subtitle: const Text('Modify basic salary and allowances'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _editSalaryStructure(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.calculate, color: Color(0xFF607D8B)),
                    title: const Text('Tax Settings'),
                    subtitle: const Text('Configure tax deductions'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _configureTaxSettings(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.schedule, color: Color(0xFF607D8B)),
                    title: const Text('Payroll Schedule'),
                    subtitle: const Text('Set salary processing dates'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _setPayrollSchedule(),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Leave Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📅 Leave Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.event, color: Color(0xFF607D8B)),
                    title: const Text('Leave Policy'),
                    subtitle: const Text('CL: 12 days/year, ML: 12 days/year'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _editLeavePolicy(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.notification_important, color: Color(0xFF607D8B)),
                    title: const Text('Leave Notifications'),
                    subtitle: const Text('Alert when leave balance is low'),
                    trailing: Switch(
                      value: true,
                      onChanged: (value) {},
                      activeColor: const Color(0xFF607D8B),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Export Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📤 Export & Backup',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.file_download, color: Color(0xFF607D8B)),
                    title: const Text('Export Payroll Data'),
                    subtitle: const Text('Download salary reports as Excel'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _exportPayrollData(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.backup, color: Color(0xFF607D8B)),
                    title: const Text('Backup Data'),
                    subtitle: const Text('Create backup of salary records'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _backupData(),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMonthSelector() {
    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFF607D8B), Color(0xFF78909C)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.calendar_month,
                color: Colors.white,
                size: 32,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Selected Month',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    _selectedMonth,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
            IconButton(
              onPressed: _selectMonth,
              icon: const Icon(Icons.edit, color: Colors.white),
            ),
            const Text('📅', style: TextStyle(fontSize: 32)),
          ],
        ),
      ),
    );
  }

  Widget _buildSalarySummary() {
    return Row(
      children: [
        Expanded(
          child: _buildSummaryCard(
            title: 'Total Payroll',
            value: '₹1,61,300',
            icon: Icons.account_balance_wallet,
            color: const Color(0xFF4CAF50),
            emoji: '💰',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Paid',
            value: '3/4',
            icon: Icons.check_circle,
            color: const Color(0xFF2196F3),
            emoji: '✅',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Pending',
            value: '1',
            icon: Icons.pending,
            color: const Color(0xFFFF5722),
            emoji: '⏳',
          ),
        ),
      ],
    );
  }

  Widget _buildTeacherFilter() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            const Icon(Icons.filter_list, color: Color(0xFF607D8B)),
            const SizedBox(width: 12),
            const Text(
              'Filter by Teacher:',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: DropdownButton<String>(
                value: _selectedTeacher,
                isExpanded: true,
                underline: Container(),
                items: ['All Teachers', ..._teacherSalaries.map((t) => t['name'] as String)]
                    .map((String value) {
                  return DropdownMenuItem<String>(
                    value: value,
                    child: Text(value),
                  );
                }).toList(),
                onChanged: (String? newValue) {
                  setState(() {
                    _selectedTeacher = newValue!;
                  });
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTeacherSalaryCard(Map<String, dynamic> teacher) {
    final bool isPaid = teacher['status'] == 'Paid';
    
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Teacher Info Header
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF607D8B).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    teacher['photo'],
                    style: const TextStyle(fontSize: 24),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        teacher['name'],
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        teacher['designation'],
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: isPaid ? Colors.green : Colors.orange,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    teacher['status'],
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Salary Breakdown
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                children: [
                  _buildSalaryRow('Basic Salary', '₹${teacher['basicSalary']}', Colors.blue),
                  const SizedBox(height: 8),
                  _buildSalaryRow('Allowances', '₹${teacher['allowances']}', Colors.green),
                  const SizedBox(height: 8),
                  _buildSalaryRow('Deductions', '₹${teacher['deductions']}', Colors.red),
                  const Divider(thickness: 2),
                  _buildSalaryRow('Net Salary', '₹${teacher['netSalary']}', const Color(0xFF607D8B), isTotal: true),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Leave Information
            Row(
              children: [
                Expanded(
                  child: _buildLeaveInfo('CL Used', '${teacher['clTaken']}/12', Colors.orange),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildLeaveInfo('ML Used', '${teacher['mlTaken']}/12', Colors.purple),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildLeaveInfo('CL Balance', '${teacher['clBalance']}', Colors.green),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildLeaveInfo('ML Balance', '${teacher['mlBalance']}', Colors.blue),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _viewSalaryDetails(teacher),
                    icon: const Icon(Icons.visibility),
                    label: const Text('View Details'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF607D8B),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                if (!isPaid)
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _paySalary(teacher),
                      icon: const Icon(Icons.payment),
                      label: const Text('Pay Now'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green,
                        foregroundColor: Colors.white,
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

  Widget _buildLeaveSummary() {
    return Row(
      children: [
        Expanded(
          child: _buildSummaryCard(
            title: 'Total CL Used',
            value: '6 days',
            icon: Icons.event_busy,
            color: const Color(0xFFFF5722),
            emoji: '📅',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Total ML Used',
            value: '4 days',
            icon: Icons.local_hospital,
            color: const Color(0xFF9C27B0),
            emoji: '🏥',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildSummaryCard(
            title: 'Avg Leave/Teacher',
            value: '2.5 days',
            icon: Icons.trending_up,
            color: const Color(0xFF2196F3),
            emoji: '📊',
          ),
        ),
      ],
    );
  }

  Widget _buildLeaveBalanceCard(Map<String, dynamic> teacher) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  teacher['photo'],
                  style: const TextStyle(fontSize: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        teacher['name'],
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        teacher['designation'],
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Column(
                    children: [
                      const Text('CL Balance', style: TextStyle(fontSize: 12, color: Colors.grey)),
                      const SizedBox(height: 4),
                      Text(
                        '${teacher['clBalance']}/12',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.orange),
                      ),
                      const SizedBox(height: 8),
                      LinearProgressIndicator(
                        value: teacher['clBalance'] / 12,
                        backgroundColor: Colors.grey[200],
                        valueColor: const AlwaysStoppedAnimation<Color>(Colors.orange),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 20),
                Expanded(
                  child: Column(
                    children: [
                      const Text('ML Balance', style: TextStyle(fontSize: 12, color: Colors.grey)),
                      const SizedBox(height: 4),
                      Text(
                        '${teacher['mlBalance']}/12',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.purple),
                      ),
                      const SizedBox(height: 8),
                      LinearProgressIndicator(
                        value: teacher['mlBalance'] / 12,
                        backgroundColor: Colors.grey[200],
                        valueColor: const AlwaysStoppedAnimation<Color>(Colors.purple),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryCard({
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
                fontSize: 20,
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

  Widget _buildReportCard({
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

  Widget _buildSalaryRow(String label, String amount, Color color, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            fontSize: isTotal ? 16 : 14,
          ),
        ),
        Text(
          amount,
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: isTotal ? 16 : 14,
          ),
        ),
      ],
    );
  }

  Widget _buildLeaveInfo(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
              fontSize: 16,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              color: Colors.grey[600],
              fontSize: 10,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildSalaryDistributionChart() {
    return Column(
      children: _teacherSalaries.map((teacher) {
        final percentage = teacher['netSalary'] / 161300;
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    teacher['name'].split(' ')[1], // Last name
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    '₹${teacher['netSalary']}',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: percentage,
                backgroundColor: Colors.grey[200],
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF607D8B)),
                minHeight: 8,
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildLeaveUsageChart() {
    return Column(
      children: _teacherSalaries.map((teacher) {
        final clPercentage = teacher['clTaken'] / 12;
        final mlPercentage = teacher['mlTaken'] / 12;
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                teacher['name'].split(' ')[1], // Last name
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('CL', style: TextStyle(fontSize: 12, color: Colors.orange)),
                        const SizedBox(height: 4),
                        LinearProgressIndicator(
                          value: clPercentage,
                          backgroundColor: Colors.grey[200],
                          valueColor: const AlwaysStoppedAnimation<Color>(Colors.orange),
                          minHeight: 6,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('ML', style: TextStyle(fontSize: 12, color: Colors.purple)),
                        const SizedBox(height: 4),
                        LinearProgressIndicator(
                          value: mlPercentage,
                          backgroundColor: Colors.grey[200],
                          valueColor: const AlwaysStoppedAnimation<Color>(Colors.purple),
                          minHeight: 6,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  void _selectMonth() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('📅 Select Month'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            'January 2024', 'February 2024', 'March 2024', 'April 2024'
          ].map((month) => ListTile(
            title: Text(month),
            trailing: _selectedMonth == month ? const Icon(Icons.check, color: Colors.green) : null,
            onTap: () {
              setState(() {
                _selectedMonth = month;
              });
              Navigator.pop(context);
            },
          )).toList(),
        ),
      ),
    );
  }

  void _processSalary() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('💰 Process Salary'),
        content: const Text('Process salary for all teachers for the selected month?'),
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
                  content: Text('Salary processing initiated! 💰'),
                  backgroundColor: Colors.green,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF607D8B)),
            child: const Text('Process', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _showPayrollReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Payroll report functionality will be implemented! 📊'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _viewSalaryDetails(Map<String, dynamic> teacher) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('💰 ${teacher['name']} - Salary Details'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildDetailRow('Basic Salary', '₹${teacher['basicSalary']}'),
              _buildDetailRow('Allowances', '₹${teacher['allowances']}'),
              _buildDetailRow('Deductions', '₹${teacher['deductions']}'),
              const Divider(),
              _buildDetailRow('Net Salary', '₹${teacher['netSalary']}', isTotal: true),
              const SizedBox(height: 16),
              const Text('Leave Information:', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              _buildDetailRow('CL Taken', '${teacher['clTaken']} days'),
              _buildDetailRow('ML Taken', '${teacher['mlTaken']} days'),
              _buildDetailRow('CL Balance', '${teacher['clBalance']} days'),
              _buildDetailRow('ML Balance', '${teacher['mlBalance']} days'),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value, {bool isTotal = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: isTotal ? const Color(0xFF607D8B) : null,
            ),
          ),
        ],
      ),
    );
  }

  void _paySalary(Map<String, dynamic> teacher) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('💳 Pay Salary - ${teacher['name']}'),
        content: Text('Process salary payment of ₹${teacher['netSalary']} for ${teacher['name']}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() {
                teacher['status'] = 'Paid';
                teacher['paymentDate'] = DateTime.now().toString().split(' ')[0];
              });
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Salary paid to ${teacher['name']}! 💰'),
                  backgroundColor: Colors.green,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: const Text('Pay Now', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _editSalaryStructure() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Edit salary structure functionality will be implemented! ✏️'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _configureTaxSettings() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Tax settings configuration will be implemented! 🧮'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _setPayrollSchedule() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Payroll schedule setting will be implemented! 📅'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _editLeavePolicy() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Leave policy editing will be implemented! 📝'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _exportPayrollData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Payroll data export will be implemented! 📤'),
        backgroundColor: Color(0xFF607D8B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _backupData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Data backup functionality will be implemented! 💾'),
        backgroundColor: Color(0xFF607D8B),
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