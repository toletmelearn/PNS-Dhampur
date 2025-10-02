import 'package:flutter/material.dart';

class FeesDefaulterPage extends StatefulWidget {
  const FeesDefaulterPage({super.key});

  @override
  State<FeesDefaulterPage> createState() => _FeesDefaulterPageState();
}

class _FeesDefaulterPageState extends State<FeesDefaulterPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedOverduePeriod = 'All Periods';
  String _selectedAmount = 'All Amounts';

  // Sample data for Defaulters
  final List<Map<String, dynamic>> _defaulters = [
    {
      'id': 'DEF001',
      'studentId': 'STU003',
      'studentName': 'Arjun Kumar',
      'class': 'Class 8',
      'section': 'A',
      'rollNumber': '008',
      'fatherName': 'Rajesh Kumar',
      'contactNumber': '+91 9876543210',
      'totalDue': 12000.0,
      'monthsOverdue': 3,
      'lastPaymentDate': '2023-10-15',
      'overdueMonths': ['November 2023', 'December 2023', 'January 2024'],
      'lateFee': 600.0,
      'remindersSent': 5,
      'lastReminderDate': '2024-01-20',
      'status': 'Critical',
      'remarks': 'Parent contacted multiple times',
      'paymentPlan': false,
      'address': '123 Main Street, Dhampur',
      'email': 'rajesh.kumar@email.com',
    },
    {
      'id': 'DEF002',
      'studentId': 'STU005',
      'studentName': 'Priya Gupta',
      'class': 'Class 9',
      'section': 'B',
      'rollNumber': '022',
      'fatherName': 'Suresh Gupta',
      'contactNumber': '+91 9876543211',
      'totalDue': 8500.0,
      'monthsOverdue': 2,
      'lastPaymentDate': '2023-11-20',
      'overdueMonths': ['December 2023', 'January 2024'],
      'lateFee': 400.0,
      'remindersSent': 3,
      'lastReminderDate': '2024-01-18',
      'status': 'High',
      'remarks': 'Promised payment by month end',
      'paymentPlan': true,
      'address': '456 Park Avenue, Dhampur',
      'email': 'suresh.gupta@email.com',
    },
    {
      'id': 'DEF003',
      'studentId': 'STU007',
      'studentName': 'Rohit Singh',
      'class': 'Class 10',
      'section': 'A',
      'rollNumber': '015',
      'fatherName': 'Vikram Singh',
      'contactNumber': '+91 9876543212',
      'totalDue': 5000.0,
      'monthsOverdue': 1,
      'lastPaymentDate': '2023-12-10',
      'overdueMonths': ['January 2024'],
      'lateFee': 200.0,
      'remindersSent': 2,
      'lastReminderDate': '2024-01-15',
      'status': 'Medium',
      'remarks': 'Recent defaulter, first reminder sent',
      'paymentPlan': false,
      'address': '789 School Road, Dhampur',
      'email': 'vikram.singh@email.com',
    },
    {
      'id': 'DEF004',
      'studentId': 'STU009',
      'studentName': 'Anita Sharma',
      'class': 'Class 7',
      'section': 'C',
      'rollNumber': '030',
      'fatherName': 'Ramesh Sharma',
      'contactNumber': '+91 9876543213',
      'totalDue': 15000.0,
      'monthsOverdue': 4,
      'lastPaymentDate': '2023-09-25',
      'overdueMonths': ['October 2023', 'November 2023', 'December 2023', 'January 2024'],
      'lateFee': 800.0,
      'remindersSent': 8,
      'lastReminderDate': '2024-01-22',
      'status': 'Critical',
      'remarks': 'Legal notice sent',
      'paymentPlan': false,
      'address': '321 College Street, Dhampur',
      'email': 'ramesh.sharma@email.com',
    },
  ];

  final List<Map<String, dynamic>> _statistics = [
    {'title': 'Total Defaulters', 'value': '47', 'icon': Icons.warning, 'color': Colors.red},
    {'title': 'Amount Overdue', 'value': '₹2,85,000', 'icon': Icons.money_off, 'color': Colors.orange},
    {'title': 'Critical Cases', 'value': '12', 'icon': Icons.priority_high, 'color': Colors.red[800]},
    {'title': 'Recovery Rate', 'value': '68%', 'icon': Icons.trending_up, 'color': Colors.green},
  ];

  final List<Map<String, dynamic>> _recentActions = [
    {
      'studentName': 'Arjun Kumar',
      'action': 'Reminder Sent',
      'method': 'SMS',
      'time': '2 hours ago',
      'status': 'Delivered',
    },
    {
      'studentName': 'Priya Gupta',
      'action': 'Payment Plan',
      'method': 'Meeting',
      'time': '1 day ago',
      'status': 'Agreed',
    },
    {
      'studentName': 'Anita Sharma',
      'action': 'Legal Notice',
      'method': 'Post',
      'time': '3 days ago',
      'status': 'Sent',
    },
  ];

  final List<Map<String, dynamic>> _recoveryStrategies = [
    {
      'title': 'SMS Reminders',
      'description': 'Automated SMS notifications',
      'icon': Icons.sms,
      'color': Colors.blue,
      'effectiveness': '75%',
    },
    {
      'title': 'Phone Calls',
      'description': 'Direct parent contact',
      'icon': Icons.phone,
      'color': Colors.green,
      'effectiveness': '85%',
    },
    {
      'title': 'Email Notices',
      'description': 'Formal email notifications',
      'icon': Icons.email,
      'color': Colors.orange,
      'effectiveness': '60%',
    },
    {
      'title': 'Home Visits',
      'description': 'Personal visits by staff',
      'icon': Icons.home,
      'color': Colors.purple,
      'effectiveness': '90%',
    },
    {
      'title': 'Payment Plans',
      'description': 'Flexible payment options',
      'icon': Icons.schedule,
      'color': Colors.teal,
      'effectiveness': '80%',
    },
    {
      'title': 'Legal Action',
      'description': 'Legal notices and procedures',
      'icon': Icons.gavel,
      'color': Colors.red,
      'effectiveness': '95%',
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
          'Fees Defaulters',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF5722),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Defaulters'),
            Tab(text: 'Recovery'),
            Tab(text: 'Reports'),
            Tab(text: 'Settings'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDefaultersTab(),
          _buildRecoveryTab(),
          _buildReportsTab(),
          _buildSettingsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _sendBulkReminders(),
        backgroundColor: const Color(0xFFFF5722),
        child: const Icon(Icons.notifications_active, color: Colors.white),
      ),
    );
  }

  Widget _buildDefaultersTab() {
    return Column(
      children: [
        // Statistics Cards
        Container(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: _statistics.map((stat) {
              return Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: stat['color'].withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: stat['color'].withOpacity(0.3)),
                  ),
                  child: Column(
                    children: [
                      Icon(stat['icon'], color: stat['color'], size: 24),
                      const SizedBox(height: 8),
                      Text(
                        stat['value'],
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: stat['color'],
                        ),
                      ),
                      Text(
                        stat['title'],
                        style: const TextStyle(fontSize: 10),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        ),

        // Filters
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedClass,
                  decoration: const InputDecoration(
                    labelText: 'Class',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Classes', 'Class 10', 'Class 9', 'Class 8', 'Class 7', 'Class 6']
                      .map((String value) {
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
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedOverduePeriod,
                  decoration: const InputDecoration(
                    labelText: 'Overdue Period',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Periods', '1 Month', '2 Months', '3+ Months', '6+ Months']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedOverduePeriod = newValue!;
                    });
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedAmount,
                  decoration: const InputDecoration(
                    labelText: 'Amount Range',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Amounts', '< ₹5,000', '₹5,000 - ₹10,000', '> ₹10,000']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedAmount = newValue!;
                    });
                  },
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // Defaulters List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _defaulters.length,
            itemBuilder: (context, index) {
              final defaulter = _defaulters[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                elevation: 2,
                child: ExpansionTile(
                  leading: CircleAvatar(
                    backgroundColor: _getStatusColor(defaulter['status']),
                    child: Text(
                      defaulter['studentName'][0],
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                  ),
                  title: Text(
                    defaulter['studentName'],
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text('${defaulter['class']} - ${defaulter['section']} | ${defaulter['monthsOverdue']} months overdue'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: _getStatusColor(defaulter['status']).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          defaulter['status'],
                          style: TextStyle(
                            color: _getStatusColor(defaulter['status']),
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '₹${defaulter['totalDue'].toStringAsFixed(0)}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red),
                      ),
                    ],
                  ),
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Roll Number', defaulter['rollNumber']),
                              ),
                              Expanded(
                                child: _buildDetailRow('Father\'s Name', defaulter['fatherName']),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Contact', defaulter['contactNumber']),
                              ),
                              Expanded(
                                child: _buildDetailRow('Email', defaulter['email']),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Total Due', '₹${defaulter['totalDue'].toStringAsFixed(0)}'),
                              ),
                              Expanded(
                                child: _buildDetailRow('Late Fee', '₹${defaulter['lateFee'].toStringAsFixed(0)}'),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Last Payment', defaulter['lastPaymentDate']),
                              ),
                              Expanded(
                                child: _buildDetailRow('Reminders Sent', defaulter['remindersSent'].toString()),
                              ),
                            ],
                          ),
                          _buildDetailRow('Address', defaulter['address']),
                          _buildDetailRow('Overdue Months', defaulter['overdueMonths'].join(', ')),
                          if (defaulter['remarks'].isNotEmpty)
                            _buildDetailRow('Remarks', defaulter['remarks']),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _sendReminder(defaulter),
                                  icon: const Icon(Icons.notifications, size: 16),
                                  label: const Text('Remind'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.orange,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _callParent(defaulter),
                                  icon: const Icon(Icons.phone, size: 16),
                                  label: const Text('Call'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.green,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _createPaymentPlan(defaulter),
                                  icon: const Icon(Icons.schedule, size: 16),
                                  label: const Text('Plan'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.blue,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _recordPayment(defaulter),
                                  icon: const Icon(Icons.payment, size: 16),
                                  label: const Text('Record Payment'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.teal,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _legalAction(defaulter),
                                  icon: const Icon(Icons.gavel, size: 16),
                                  label: const Text('Legal Action'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.red,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildRecoveryTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '🎯 Recovery Strategies',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Recovery Strategies Grid
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.2,
            ),
            itemCount: _recoveryStrategies.length,
            itemBuilder: (context, index) {
              final strategy = _recoveryStrategies[index];
              return Card(
                elevation: 2,
                child: InkWell(
                  onTap: () => _executeStrategy(strategy),
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(strategy['icon'], color: strategy['color'], size: 32),
                        const SizedBox(height: 12),
                        Text(
                          strategy['title'],
                          style: const TextStyle(fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          strategy['description'],
                          style: const TextStyle(fontSize: 12, color: Colors.grey),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: strategy['color'].withOpacity(0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            strategy['effectiveness'],
                            style: TextStyle(
                              color: strategy['color'],
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),

          const SizedBox(height: 24),

          // Recent Actions
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Recent Recovery Actions',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  ..._recentActions.map((action) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey[300]!),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: _getActionColor(action['action']),
                            radius: 16,
                            child: Icon(
                              _getActionIcon(action['action']),
                              color: Colors.white,
                              size: 16,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  action['studentName'],
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                Text(
                                  '${action['action']} • ${action['method']} • ${action['time']}',
                                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: _getStatusColorForAction(action['status']).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              action['status'],
                              style: TextStyle(
                                color: _getStatusColorForAction(action['status']),
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                ],
              ),
            ),
          ),
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
          const Text(
            '📊 Defaulter Reports',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Report Generation
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildReportCard('Daily Defaulters', 'Today\'s overdue list', Icons.today, Colors.red),
              _buildReportCard('Monthly Analysis', 'Month-wise defaulter trends', Icons.calendar_month, Colors.orange),
              _buildReportCard('Class-wise Report', 'Defaulters by class', Icons.school, Colors.blue),
              _buildReportCard('Recovery Report', 'Payment recovery analysis', Icons.trending_up, Colors.green),
              _buildReportCard('Legal Cases', 'Legal action status', Icons.gavel, Colors.purple),
              _buildReportCard('Payment Plans', 'Active payment plans', Icons.schedule, Colors.teal),
            ],
          ),

          const SizedBox(height: 24),

          // Quick Stats
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Quick Statistics',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: _buildStatItem('This Month', '12 New Defaulters', Icons.trending_up, Colors.red),
                      ),
                      Expanded(
                        child: _buildStatItem('Recovered', '₹45,000 This Week', Icons.check_circle, Colors.green),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _buildStatItem('Critical Cases', '8 Require Action', Icons.priority_high, Colors.orange),
                      ),
                      Expanded(
                        child: _buildStatItem('Success Rate', '72% Recovery', Icons.star, Colors.blue),
                      ),
                    ],
                  ),
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
          const Text(
            '⚙️ Defaulter Settings',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Reminder Settings
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Reminder Settings',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildSettingItem('Auto Reminders', 'Send automatic reminders', true),
                  _buildSettingItem('SMS Notifications', 'SMS reminder alerts', true),
                  _buildSettingItem('Email Reminders', 'Email notification system', false),
                  _buildSettingItem('WhatsApp Alerts', 'WhatsApp reminder messages', true),
                  _buildSettingItem('Call Reminders', 'Automated call reminders', false),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Recovery Settings
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Recovery Settings',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildSettingItem('Late Fee Calculation', 'Auto-calculate late fees', true),
                  _buildSettingItem('Payment Plans', 'Enable flexible payment plans', true),
                  _buildSettingItem('Grace Period', 'Allow grace period for payments', false),
                  _buildSettingItem('Legal Action Alerts', 'Notify before legal action', true),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // System Settings
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'System Settings',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  ListTile(
                    leading: const Icon(Icons.schedule),
                    title: const Text('Reminder Frequency'),
                    subtitle: const Text('Set reminder intervals'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _setReminderFrequency(),
                  ),
                  ListTile(
                    leading: const Icon(Icons.money),
                    title: const Text('Late Fee Rules'),
                    subtitle: const Text('Configure late fee calculation'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _configureLateFeesRules(),
                  ),
                  ListTile(
                    leading: const Icon(Icons.security),
                    title: const Text('Access Control'),
                    subtitle: const Text('Manage user permissions'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _manageAccessControl(),
                  ),
                  ListTile(
                    leading: const Icon(Icons.backup),
                    title: const Text('Data Backup'),
                    subtitle: const Text('Backup defaulter data'),
                    trailing: ElevatedButton(
                      onPressed: () => _backupData(),
                      child: const Text('Backup'),
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

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.w500),
          ),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }

  Widget _buildReportCard(String title, String subtitle, IconData icon, Color color) {
    return Card(
      elevation: 2,
      child: InkWell(
        onTap: () => _generateReport(title),
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: color, size: 32),
              const SizedBox(height: 12),
              Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 12, color: Colors.grey),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatItem(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            title,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
          ),
          Text(
            value,
            style: TextStyle(fontSize: 10, color: color),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildSettingItem(String title, String subtitle, bool value) {
    return ListTile(
      title: Text(title),
      subtitle: Text(subtitle),
      trailing: Switch(
        value: value,
        onChanged: (bool newValue) {
          setState(() {
            // Update setting value
          });
        },
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'critical':
        return Colors.red[800]!;
      case 'high':
        return Colors.red;
      case 'medium':
        return Colors.orange;
      case 'low':
        return Colors.yellow[700]!;
      default:
        return Colors.grey;
    }
  }

  Color _getActionColor(String action) {
    switch (action.toLowerCase()) {
      case 'reminder sent':
        return Colors.orange;
      case 'payment plan':
        return Colors.blue;
      case 'legal notice':
        return Colors.red;
      case 'phone call':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  IconData _getActionIcon(String action) {
    switch (action.toLowerCase()) {
      case 'reminder sent':
        return Icons.notifications;
      case 'payment plan':
        return Icons.schedule;
      case 'legal notice':
        return Icons.gavel;
      case 'phone call':
        return Icons.phone;
      default:
        return Icons.info;
    }
  }

  Color _getStatusColorForAction(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
      case 'agreed':
      case 'sent':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  void _sendBulkReminders() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Sending bulk reminders to all defaulters...')),
    );
  }

  void _sendReminder(Map<String, dynamic> defaulter) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Sending reminder to ${defaulter['studentName']}')),
    );
  }

  void _callParent(Map<String, dynamic> defaulter) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Calling ${defaulter['fatherName']} at ${defaulter['contactNumber']}')),
    );
  }

  void _createPaymentPlan(Map<String, dynamic> defaulter) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Creating payment plan for ${defaulter['studentName']}')),
    );
  }

  void _recordPayment(Map<String, dynamic> defaulter) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Recording payment for ${defaulter['studentName']}')),
    );
  }

  void _legalAction(Map<String, dynamic> defaulter) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Initiating legal action for ${defaulter['studentName']}')),
    );
  }

  void _executeStrategy(Map<String, dynamic> strategy) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Executing ${strategy['title']} strategy...')),
    );
  }

  void _generateReport(String reportType) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating $reportType...')),
    );
  }

  void _setReminderFrequency() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening reminder frequency settings...')),
    );
  }

  void _configureLateFeesRules() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening late fee configuration...')),
    );
  }

  void _manageAccessControl() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening access control settings...')),
    );
  }

  void _backupData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Starting data backup...')),
    );
  }
}