import 'package:flutter/material.dart';

class FeeManagementPage extends StatefulWidget {
  final String token;

  const FeeManagementPage({Key? key, required this.token}) : super(key: key);

  @override
  State<FeeManagementPage> createState() => _FeeManagementPageState();
}

class _FeeManagementPageState extends State<FeeManagementPage> with TickerProviderStateMixin {
  late TabController _tabController;
  final _searchController = TextEditingController();

  // Sample data - replace with API calls
  final List<Map<String, dynamic>> _students = [
    {
      'id': 1,
      'name': 'Aarav Sharma',
      'class': '5th',
      'rollNumber': '101',
      'totalFee': 12000,
      'paidAmount': 8000,
      'pendingAmount': 4000,
      'lastPayment': '15 Nov 2024',
      'status': 'Partial',
      'payments': [
        {'date': '15 Apr 2024', 'amount': 4000, 'type': 'Admission Fee'},
        {'date': '15 Jul 2024', 'amount': 2000, 'type': 'Tuition Fee'},
        {'date': '15 Nov 2024', 'amount': 2000, 'type': 'Tuition Fee'},
      ]
    },
    {
      'id': 2,
      'name': 'Priya Patel',
      'class': '3rd',
      'rollNumber': '205',
      'totalFee': 10000,
      'paidAmount': 10000,
      'pendingAmount': 0,
      'lastPayment': '20 Nov 2024',
      'status': 'Paid',
      'payments': [
        {'date': '10 Apr 2024', 'amount': 5000, 'type': 'Admission Fee'},
        {'date': '20 Nov 2024', 'amount': 5000, 'type': 'Tuition Fee'},
      ]
    },
    {
      'id': 3,
      'name': 'Rohit Kumar',
      'class': '7th',
      'rollNumber': '301',
      'totalFee': 15000,
      'paidAmount': 0,
      'pendingAmount': 15000,
      'lastPayment': 'No Payment',
      'status': 'Pending',
      'payments': []
    },
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
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
              child: const Text('💰', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Fee Management',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF4CAF50),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: '📊 Overview'),
            Tab(text: '👥 Students'),
            Tab(text: '💳 Payments'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildOverviewTab(),
          _buildStudentsTab(),
          _buildPaymentsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddPaymentDialog(),
        backgroundColor: const Color(0xFF6C63FF),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text(
          'Add Payment',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Widget _buildOverviewTab() {
    final totalStudents = _students.length;
    final totalFeeAmount = _students.fold<double>(0, (sum, student) => sum + student['totalFee']);
    final totalPaidAmount = _students.fold<double>(0, (sum, student) => sum + student['paidAmount']);
    final totalPendingAmount = _students.fold<double>(0, (sum, student) => sum + student['pendingAmount']);
    final paidStudents = _students.where((s) => s['status'] == 'Paid').length;
    final partialStudents = _students.where((s) => s['status'] == 'Partial').length;
    final pendingStudents = _students.where((s) => s['status'] == 'Pending').length;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Summary Cards
          Row(
            children: [
              Expanded(
                child: _buildSummaryCard(
                  title: 'Total Students',
                  value: totalStudents.toString(),
                  icon: Icons.people,
                  color: const Color(0xFF2196F3),
                  emoji: '👥',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSummaryCard(
                  title: 'Total Fee',
                  value: '₹${totalFeeAmount.toStringAsFixed(0)}',
                  icon: Icons.account_balance_wallet,
                  color: const Color(0xFF9C27B0),
                  emoji: '💰',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildSummaryCard(
                  title: 'Collected',
                  value: '₹${totalPaidAmount.toStringAsFixed(0)}',
                  icon: Icons.check_circle,
                  color: const Color(0xFF4CAF50),
                  emoji: '✅',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSummaryCard(
                  title: 'Pending',
                  value: '₹${totalPendingAmount.toStringAsFixed(0)}',
                  icon: Icons.pending,
                  color: const Color(0xFFFF5722),
                  emoji: '⏳',
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Collection Progress
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                gradient: const LinearGradient(
                  colors: [Color(0xFF6C63FF), Color(0xFF9C88FF)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📈 Collection Progress',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),
                  LinearProgressIndicator(
                    value: totalPaidAmount / totalFeeAmount,
                    backgroundColor: Colors.white.withOpacity(0.3),
                    valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                    minHeight: 8,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    '${((totalPaidAmount / totalFeeAmount) * 100).toStringAsFixed(1)}% Collected',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Status Distribution
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📊 Payment Status Distribution',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildStatusRow('Fully Paid', paidStudents, const Color(0xFF4CAF50), '✅'),
                  const SizedBox(height: 12),
                  _buildStatusRow('Partially Paid', partialStudents, const Color(0xFFFF9800), '⚠️'),
                  const SizedBox(height: 12),
                  _buildStatusRow('Pending', pendingStudents, const Color(0xFFFF5722), '❌'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStudentsTab() {
    return Column(
      children: [
        // Search Bar
        Container(
          margin: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.grey.withOpacity(0.1),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: TextField(
            controller: _searchController,
            decoration: const InputDecoration(
              hintText: 'Search students... 🔍',
              prefixIcon: Icon(Icons.search, color: Color(0xFF6C63FF)),
              border: InputBorder.none,
              contentPadding: EdgeInsets.all(16),
            ),
            onChanged: (value) {
              setState(() {});
            },
          ),
        ),
        // Students List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _students.length,
            itemBuilder: (context, index) {
              final student = _students[index];
              return _buildStudentCard(student);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildPaymentsTab() {
    final allPayments = <Map<String, dynamic>>[];
    for (final student in _students) {
      for (final payment in student['payments']) {
        allPayments.add({
          ...payment,
          'studentName': student['name'],
          'class': student['class'],
        });
      }
    }
    allPayments.sort((a, b) => b['date'].compareTo(a['date']));

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: allPayments.length,
      itemBuilder: (context, index) {
        final payment = allPayments[index];
        return _buildPaymentCard(payment);
      },
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
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusRow(String label, int count, Color color, String emoji) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(emoji, style: const TextStyle(fontSize: 16)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            count.toString(),
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStudentCard(Map<String, dynamic> student) {
    final paymentProgress = student['paidAmount'] / student['totalFee'];
    Color statusColor;
    String statusEmoji;
    
    switch (student['status']) {
      case 'Paid':
        statusColor = const Color(0xFF4CAF50);
        statusEmoji = '✅';
        break;
      case 'Partial':
        statusColor = const Color(0xFFFF9800);
        statusEmoji = '⚠️';
        break;
      default:
        statusColor = const Color(0xFFFF5722);
        statusEmoji = '❌';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: statusColor.withOpacity(0.3), width: 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: statusColor.withOpacity(0.1),
                  child: Text(
                    student['name'][0],
                    style: TextStyle(
                      color: statusColor,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
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
                        'Class: ${student['class']} | Roll: ${student['rollNumber']}',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(statusEmoji),
                      const SizedBox(width: 4),
                      Text(
                        student['status'],
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.bold,
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
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Total Fee: ₹${student['totalFee']}',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      Text(
                        'Paid: ₹${student['paidAmount']}',
                        style: TextStyle(color: Colors.green[600]),
                      ),
                      Text(
                        'Pending: ₹${student['pendingAmount']}',
                        style: TextStyle(color: Colors.red[600]),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      '${(paymentProgress * 100).toStringAsFixed(0)}%',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Text('Completed'),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),
            LinearProgressIndicator(
              value: paymentProgress,
              backgroundColor: Colors.grey[300],
              valueColor: AlwaysStoppedAnimation<Color>(statusColor),
              minHeight: 6,
            ),
            const SizedBox(height: 8),
            Text(
              'Last Payment: ${student['lastPayment']}',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentCard(Map<String, dynamic> payment) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFF4CAF50).withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(
            Icons.payment,
            color: Color(0xFF4CAF50),
          ),
        ),
        title: Text(
          payment['studentName'],
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Class: ${payment['class']} | ${payment['type']}'),
            Text(
              payment['date'],
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: const Color(0xFF4CAF50),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '₹${payment['amount']}',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ),
    );
  }

  void _showAddPaymentDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.add_circle, color: Color(0xFF6C63FF)),
            SizedBox(width: 8),
            Text('Add Payment'),
          ],
        ),
        content: const Text('Payment form will be implemented here! 💳'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6C63FF),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text('Add', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }
}