import 'package:flutter/material.dart';

class FeesCollectionPage extends StatefulWidget {
  const FeesCollectionPage({super.key});

  @override
  State<FeesCollectionPage> createState() => _FeesCollectionPageState();
}

class _FeesCollectionPageState extends State<FeesCollectionPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedMonth = 'All Months';
  String _selectedPaymentStatus = 'All Status';

  // Sample data for Fee Collections
  final List<Map<String, dynamic>> _feeCollections = [
    {
      'id': 'FC001',
      'studentId': 'STU001',
      'studentName': 'Aarav Sharma',
      'class': 'Class 10',
      'section': 'A',
      'rollNumber': '001',
      'feeType': 'Monthly Fee',
      'amount': 5000.0,
      'paidAmount': 5000.0,
      'pendingAmount': 0.0,
      'paymentDate': '2024-01-15',
      'dueDate': '2024-01-10',
      'status': 'Paid',
      'paymentMethod': 'Online',
      'transactionId': 'TXN123456789',
      'receiptNumber': 'RCP001',
      'month': 'January',
      'year': '2024',
      'lateFee': 0.0,
      'discount': 0.0,
      'remarks': 'Payment completed on time',
    },
    {
      'id': 'FC002',
      'studentId': 'STU002',
      'studentName': 'Diya Patel',
      'class': 'Class 9',
      'section': 'B',
      'rollNumber': '015',
      'feeType': 'Monthly Fee',
      'amount': 4500.0,
      'paidAmount': 2000.0,
      'pendingAmount': 2500.0,
      'paymentDate': '2024-01-20',
      'dueDate': '2024-01-10',
      'status': 'Partial',
      'paymentMethod': 'Cash',
      'transactionId': '',
      'receiptNumber': 'RCP002',
      'month': 'January',
      'year': '2024',
      'lateFee': 100.0,
      'discount': 0.0,
      'remarks': 'Partial payment received',
    },
    {
      'id': 'FC003',
      'studentId': 'STU003',
      'studentName': 'Arjun Kumar',
      'class': 'Class 8',
      'section': 'A',
      'rollNumber': '008',
      'feeType': 'Monthly Fee',
      'amount': 4000.0,
      'paidAmount': 0.0,
      'pendingAmount': 4000.0,
      'paymentDate': '',
      'dueDate': '2024-01-10',
      'status': 'Pending',
      'paymentMethod': '',
      'transactionId': '',
      'receiptNumber': '',
      'month': 'January',
      'year': '2024',
      'lateFee': 200.0,
      'discount': 0.0,
      'remarks': 'Payment overdue',
    },
    {
      'id': 'FC004',
      'studentId': 'STU004',
      'studentName': 'Kavya Singh',
      'class': 'Class 10',
      'section': 'B',
      'rollNumber': '025',
      'feeType': 'Admission Fee',
      'amount': 15000.0,
      'paidAmount': 15000.0,
      'pendingAmount': 0.0,
      'paymentDate': '2024-01-05',
      'dueDate': '2024-01-01',
      'status': 'Paid',
      'paymentMethod': 'Bank Transfer',
      'transactionId': 'TXN987654321',
      'receiptNumber': 'RCP003',
      'month': 'January',
      'year': '2024',
      'lateFee': 0.0,
      'discount': 1000.0,
      'remarks': 'Early bird discount applied',
    },
  ];

  final List<Map<String, dynamic>> _statistics = [
    {'title': 'Total Collection', 'value': '₹2,45,000', 'icon': Icons.account_balance_wallet, 'color': Colors.green},
    {'title': 'Pending Amount', 'value': '₹45,000', 'icon': Icons.pending_actions, 'color': Colors.orange},
    {'title': 'Students Paid', 'value': '189', 'icon': Icons.check_circle, 'color': Colors.blue},
    {'title': 'Overdue Payments', 'value': '23', 'icon': Icons.warning, 'color': Colors.red},
  ];

  final List<Map<String, dynamic>> _feeStructure = [
    {
      'class': 'Class 10',
      'monthlyFee': 5000.0,
      'admissionFee': 15000.0,
      'examFee': 2000.0,
      'transportFee': 3000.0,
      'libraryFee': 500.0,
      'labFee': 1500.0,
      'totalAnnual': 78000.0,
    },
    {
      'class': 'Class 9',
      'monthlyFee': 4500.0,
      'admissionFee': 12000.0,
      'examFee': 1800.0,
      'transportFee': 3000.0,
      'libraryFee': 500.0,
      'labFee': 1200.0,
      'totalAnnual': 68000.0,
    },
    {
      'class': 'Class 8',
      'monthlyFee': 4000.0,
      'admissionFee': 10000.0,
      'examFee': 1500.0,
      'transportFee': 2500.0,
      'libraryFee': 400.0,
      'labFee': 1000.0,
      'totalAnnual': 58000.0,
    },
  ];

  final List<Map<String, dynamic>> _recentTransactions = [
    {
      'studentName': 'Aarav Sharma',
      'amount': 5000.0,
      'method': 'Online',
      'time': '10:30 AM',
      'status': 'Success',
    },
    {
      'studentName': 'Diya Patel',
      'amount': 2000.0,
      'method': 'Cash',
      'time': '11:15 AM',
      'status': 'Success',
    },
    {
      'studentName': 'Kavya Singh',
      'amount': 15000.0,
      'method': 'Bank Transfer',
      'time': '09:45 AM',
      'status': 'Success',
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
          'Fees Collection',
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
            Tab(text: 'Collections'),
            Tab(text: 'Fee Structure'),
            Tab(text: 'Reports'),
            Tab(text: 'Settings'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildCollectionsTab(),
          _buildFeeStructureTab(),
          _buildReportsTab(),
          _buildSettingsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _collectFee(),
        backgroundColor: const Color(0xFF4CAF50),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildCollectionsTab() {
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
                  value: _selectedMonth,
                  decoration: const InputDecoration(
                    labelText: 'Month',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Months', 'January', 'February', 'March', 'April', 'May', 'June']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedMonth = newValue!;
                    });
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedPaymentStatus,
                  decoration: const InputDecoration(
                    labelText: 'Status',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Status', 'Paid', 'Partial', 'Pending', 'Overdue']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedPaymentStatus = newValue!;
                    });
                  },
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // Fee Collections List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _feeCollections.length,
            itemBuilder: (context, index) {
              final collection = _feeCollections[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                elevation: 2,
                child: ExpansionTile(
                  leading: CircleAvatar(
                    backgroundColor: _getStatusColor(collection['status']),
                    child: Text(
                      collection['studentName'][0],
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                  ),
                  title: Text(
                    collection['studentName'],
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text('${collection['class']} - ${collection['section']} | ${collection['feeType']}'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: _getStatusColor(collection['status']).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          collection['status'],
                          style: TextStyle(
                            color: _getStatusColor(collection['status']),
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '₹${collection['amount'].toStringAsFixed(0)}',
                        style: const TextStyle(fontWeight: FontWeight.bold),
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
                                child: _buildDetailRow('Roll Number', collection['rollNumber']),
                              ),
                              Expanded(
                                child: _buildDetailRow('Month/Year', '${collection['month']} ${collection['year']}'),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Total Amount', '₹${collection['amount'].toStringAsFixed(0)}'),
                              ),
                              Expanded(
                                child: _buildDetailRow('Paid Amount', '₹${collection['paidAmount'].toStringAsFixed(0)}'),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Pending Amount', '₹${collection['pendingAmount'].toStringAsFixed(0)}'),
                              ),
                              Expanded(
                                child: _buildDetailRow('Late Fee', '₹${collection['lateFee'].toStringAsFixed(0)}'),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: _buildDetailRow('Due Date', collection['dueDate']),
                              ),
                              Expanded(
                                child: _buildDetailRow('Payment Date', collection['paymentDate'].isNotEmpty ? collection['paymentDate'] : 'Not Paid'),
                              ),
                            ],
                          ),
                          if (collection['paymentMethod'].isNotEmpty)
                            Row(
                              children: [
                                Expanded(
                                  child: _buildDetailRow('Payment Method', collection['paymentMethod']),
                                ),
                                Expanded(
                                  child: _buildDetailRow('Transaction ID', collection['transactionId']),
                                ),
                              ],
                            ),
                          if (collection['remarks'].isNotEmpty)
                            _buildDetailRow('Remarks', collection['remarks']),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              if (collection['status'] != 'Paid')
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () => _collectPayment(collection),
                                    icon: const Icon(Icons.payment, size: 16),
                                    label: const Text('Collect'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.green,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ),
                              if (collection['status'] != 'Paid') const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _printReceipt(collection),
                                  icon: const Icon(Icons.print, size: 16),
                                  label: const Text('Receipt'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.blue,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _sendReminder(collection),
                                  icon: const Icon(Icons.notifications, size: 16),
                                  label: const Text('Remind'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.orange,
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

  Widget _buildFeeStructureTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '💰 Fee Structure',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Fee Structure Cards
          ...(_feeStructure.map((structure) {
            return Card(
              margin: const EdgeInsets.only(bottom: 16),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      structure['class'],
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    GridView.count(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisCount: 2,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 2.5,
                      children: [
                        _buildFeeItem('Monthly Fee', structure['monthlyFee']),
                        _buildFeeItem('Admission Fee', structure['admissionFee']),
                        _buildFeeItem('Exam Fee', structure['examFee']),
                        _buildFeeItem('Transport Fee', structure['transportFee']),
                        _buildFeeItem('Library Fee', structure['libraryFee']),
                        _buildFeeItem('Lab Fee', structure['labFee']),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green[50],
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.green[200]!),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Total Annual Fee:',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          Text(
                            '₹${structure['totalAnnual'].toStringAsFixed(0)}',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 18,
                              color: Colors.green,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList()),

          const SizedBox(height: 16),

          // Fee Management Actions
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Fee Management',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _updateFeeStructure(),
                          icon: const Icon(Icons.edit),
                          label: const Text('Update Structure'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.blue,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _addNewFeeType(),
                          icon: const Icon(Icons.add),
                          label: const Text('Add Fee Type'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                          ),
                        ),
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

  Widget _buildReportsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '📊 Collection Reports',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Recent Transactions
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Recent Transactions',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  ..._recentTransactions.map((transaction) {
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
                            backgroundColor: Colors.green,
                            radius: 16,
                            child: Icon(
                              _getPaymentMethodIcon(transaction['method']),
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
                                  transaction['studentName'],
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                Text(
                                  '${transaction['method']} • ${transaction['time']}',
                                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                '₹${transaction['amount'].toStringAsFixed(0)}',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.green.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  transaction['status'],
                                  style: const TextStyle(
                                    color: Colors.green,
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
                  }).toList(),
                ],
              ),
            ),
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
              _buildReportCard('Daily Collection', 'Today\'s collection summary', Icons.today, Colors.blue),
              _buildReportCard('Monthly Report', 'Month-wise collection analysis', Icons.calendar_month, Colors.green),
              _buildReportCard('Class-wise Report', 'Collection by class breakdown', Icons.school, Colors.orange),
              _buildReportCard('Defaulter Report', 'Overdue payments list', Icons.warning, Colors.red),
              _buildReportCard('Payment Methods', 'Payment mode analysis', Icons.payment, Colors.purple),
              _buildReportCard('Fee Structure', 'Current fee structure report', Icons.account_balance, Colors.teal),
            ],
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
            '⚙️ Collection Settings',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Payment Settings
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Payment Settings',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildSettingItem('Late Fee Calculation', 'Auto-calculate late fees', true),
                  _buildSettingItem('Payment Reminders', 'Send automatic reminders', true),
                  _buildSettingItem('Online Payments', 'Enable online payment gateway', true),
                  _buildSettingItem('Partial Payments', 'Allow partial fee payments', false),
                  _buildSettingItem('Discount Management', 'Enable discount system', true),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Notification Settings
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Notification Settings',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildSettingItem('SMS Notifications', 'Send SMS for payments', true),
                  _buildSettingItem('Email Receipts', 'Email payment receipts', true),
                  _buildSettingItem('WhatsApp Updates', 'WhatsApp payment updates', false),
                  _buildSettingItem('Push Notifications', 'App push notifications', true),
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
                    leading: const Icon(Icons.backup),
                    title: const Text('Data Backup'),
                    subtitle: const Text('Last backup: Today, 10:30 AM'),
                    trailing: ElevatedButton(
                      onPressed: () => _backupData(),
                      child: const Text('Backup Now'),
                    ),
                  ),
                  ListTile(
                    leading: const Icon(Icons.security),
                    title: const Text('Security Settings'),
                    subtitle: const Text('Configure access permissions'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _openSecuritySettings(),
                  ),
                  ListTile(
                    leading: const Icon(Icons.sync),
                    title: const Text('Data Sync'),
                    subtitle: const Text('Sync with accounting software'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _openSyncSettings(),
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

  Widget _buildFeeItem(String title, double amount) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
          const SizedBox(height: 4),
          Text(
            '₹${amount.toStringAsFixed(0)}',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
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
      case 'paid':
        return Colors.green;
      case 'partial':
        return Colors.orange;
      case 'pending':
        return Colors.red;
      case 'overdue':
        return Colors.red[800]!;
      default:
        return Colors.grey;
    }
  }

  IconData _getPaymentMethodIcon(String method) {
    switch (method.toLowerCase()) {
      case 'online':
        return Icons.computer;
      case 'cash':
        return Icons.money;
      case 'bank transfer':
        return Icons.account_balance;
      default:
        return Icons.payment;
    }
  }

  void _collectFee() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening fee collection form...')),
    );
  }

  void _collectPayment(Map<String, dynamic> collection) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Collecting payment for ${collection['studentName']}')),
    );
  }

  void _printReceipt(Map<String, dynamic> collection) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Printing receipt for ${collection['studentName']}')),
    );
  }

  void _sendReminder(Map<String, dynamic> collection) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Sending reminder to ${collection['studentName']}')),
    );
  }

  void _updateFeeStructure() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening fee structure editor...')),
    );
  }

  void _addNewFeeType() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Adding new fee type...')),
    );
  }

  void _generateReport(String reportType) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating $reportType...')),
    );
  }

  void _backupData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Starting data backup...')),
    );
  }

  void _openSecuritySettings() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening security settings...')),
    );
  }

  void _openSyncSettings() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening sync settings...')),
    );
  }
}