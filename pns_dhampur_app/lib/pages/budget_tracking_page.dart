import 'package:flutter/material.dart';

class BudgetTrackingPage extends StatefulWidget {
  final String token;

  const BudgetTrackingPage({Key? key, required this.token}) : super(key: key);

  @override
  _BudgetTrackingPageState createState() => _BudgetTrackingPageState();
}

class _BudgetTrackingPageState extends State<BudgetTrackingPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String selectedPeriod = 'Current Year';
  String selectedCategory = 'All Categories';

  final List<String> periods = ['Current Year', 'Last Year', 'Current Month', 'Last Month'];
  final List<String> categories = [
    'All Categories', 'Salaries', 'Infrastructure', 'Utilities', 
    'Supplies', 'Transportation', 'Events', 'Maintenance', 'Other'
  ];

  // Sample budget data
  final Map<String, dynamic> budgetData = {
    'totalBudget': 5000000.0,
    'totalExpenses': 3250000.0,
    'totalIncome': 4800000.0,
    'remainingBudget': 1750000.0,
  };

  // Sample expense data
  final List<Map<String, dynamic>> expenses = [
    {
      'id': 1,
      'title': 'Teacher Salaries - January',
      'category': 'Salaries',
      'amount': 450000.0,
      'date': '2024-01-31',
      'status': 'Paid',
      'description': 'Monthly salary payment for all teaching staff',
      'paymentMethod': 'Bank Transfer',
      'approvedBy': 'Principal',
    },
    {
      'id': 2,
      'title': 'Electricity Bill - January',
      'category': 'Utilities',
      'amount': 25000.0,
      'date': '2024-01-15',
      'status': 'Paid',
      'description': 'Monthly electricity charges',
      'paymentMethod': 'Online Payment',
      'approvedBy': 'Admin',
    },
    {
      'id': 3,
      'title': 'Classroom Renovation',
      'category': 'Infrastructure',
      'amount': 150000.0,
      'date': '2024-01-20',
      'status': 'Pending',
      'description': 'Renovation of Class 5A and 5B',
      'paymentMethod': 'Cheque',
      'approvedBy': 'Management',
    },
    {
      'id': 4,
      'title': 'Sports Equipment Purchase',
      'category': 'Supplies',
      'amount': 35000.0,
      'date': '2024-01-18',
      'status': 'Paid',
      'description': 'Football, cricket bats, and other sports items',
      'paymentMethod': 'Cash',
      'approvedBy': 'Sports Teacher',
    },
    {
      'id': 5,
      'title': 'School Bus Maintenance',
      'category': 'Transportation',
      'amount': 18000.0,
      'date': '2024-01-12',
      'status': 'Paid',
      'description': 'Regular maintenance and repairs',
      'paymentMethod': 'Bank Transfer',
      'approvedBy': 'Transport Head',
    },
  ];

  // Sample income data
  final List<Map<String, dynamic>> income = [
    {
      'id': 1,
      'title': 'Student Fees - January',
      'category': 'Fees',
      'amount': 380000.0,
      'date': '2024-01-31',
      'status': 'Received',
      'description': 'Monthly fee collection from students',
      'source': 'Student Payments',
    },
    {
      'id': 2,
      'title': 'Government Grant',
      'category': 'Grants',
      'amount': 200000.0,
      'date': '2024-01-25',
      'status': 'Received',
      'description': 'Educational development grant',
      'source': 'Government',
    },
    {
      'id': 3,
      'title': 'Donation - Local Business',
      'category': 'Donations',
      'amount': 50000.0,
      'date': '2024-01-20',
      'status': 'Received',
      'description': 'Donation for library books',
      'source': 'ABC Industries',
    },
    {
      'id': 4,
      'title': 'Event Revenue - Annual Day',
      'category': 'Events',
      'amount': 25000.0,
      'date': '2024-01-15',
      'status': 'Received',
      'description': 'Revenue from annual day celebrations',
      'source': 'Event Tickets',
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
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text(
          'Budget & Expenses 💳',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Colors.white,
          ),
        ),
        backgroundColor: const Color(0xFF673AB7),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Overview', icon: Icon(Icons.dashboard, size: 20)),
            Tab(text: 'Expenses', icon: Icon(Icons.money_off, size: 20)),
            Tab(text: 'Income', icon: Icon(Icons.attach_money, size: 20)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics, size: 20)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildOverviewTab(),
          _buildExpensesTab(),
          _buildIncomeTab(),
          _buildReportsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddTransactionDialog(),
        backgroundColor: const Color(0xFF673AB7),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildOverviewTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Period Selector
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 1,
                  blurRadius: 5,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                const Icon(Icons.calendar_today, color: Color(0xFF673AB7)),
                const SizedBox(width: 12),
                const Text(
                  'Period: ',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF333333),
                  ),
                ),
                Expanded(
                  child: DropdownButton<String>(
                    value: selectedPeriod,
                    isExpanded: true,
                    underline: Container(),
                    onChanged: (String? newValue) {
                      setState(() => selectedPeriod = newValue!);
                    },
                    items: periods.map<DropdownMenuItem<String>>((String value) {
                      return DropdownMenuItem<String>(
                        value: value,
                        child: Text(value),
                      );
                    }).toList(),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Budget Summary Cards
          Row(
            children: [
              Expanded(
                child: _buildSummaryCard(
                  'Total Budget',
                  '₹${_formatAmount(budgetData['totalBudget'])}',
                  Icons.account_balance_wallet,
                  const Color(0xFF2196F3),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSummaryCard(
                  'Total Income',
                  '₹${_formatAmount(budgetData['totalIncome'])}',
                  Icons.trending_up,
                  const Color(0xFF4CAF50),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildSummaryCard(
                  'Total Expenses',
                  '₹${_formatAmount(budgetData['totalExpenses'])}',
                  Icons.trending_down,
                  const Color(0xFFFF5722),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSummaryCard(
                  'Remaining',
                  '₹${_formatAmount(budgetData['remainingBudget'])}',
                  Icons.savings,
                  const Color(0xFF9C27B0),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Budget Progress
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF673AB7), Color(0xFF9C27B0)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '📊 Budget Utilization',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),
                LinearProgressIndicator(
                  value: budgetData['totalExpenses'] / budgetData['totalBudget'],
                  backgroundColor: Colors.white.withOpacity(0.3),
                  valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                  minHeight: 8,
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${((budgetData['totalExpenses'] / budgetData['totalBudget']) * 100).toStringAsFixed(1)}% Used',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    Text(
                      '${(100 - (budgetData['totalExpenses'] / budgetData['totalBudget']) * 100).toStringAsFixed(1)}% Remaining',
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Category-wise Expenses
          const Text(
            '📈 Category-wise Expenses',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 12),
          ..._getCategoryExpenses().entries.map((entry) => 
            _buildCategoryExpenseCard(entry.key, entry.value)
          ).toList(),
        ],
      ),
    );
  }

  Widget _buildExpensesTab() {
    final filteredExpenses = expenses.where((expense) {
      final matchesCategory = selectedCategory == 'All Categories' || 
                             expense['category'] == selectedCategory;
      return matchesCategory;
    }).toList();

    return Column(
      children: [
        // Filter Section
        Container(
          padding: const EdgeInsets.all(16),
          color: Colors.white,
          child: Row(
            children: [
              const Icon(Icons.filter_list, color: Color(0xFF673AB7)),
              const SizedBox(width: 12),
              const Text(
                'Category: ',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF333333),
                ),
              ),
              Expanded(
                child: DropdownButton<String>(
                  value: selectedCategory,
                  isExpanded: true,
                  underline: Container(),
                  onChanged: (String? newValue) {
                    setState(() => selectedCategory = newValue!);
                  },
                  items: categories.map<DropdownMenuItem<String>>((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value, style: const TextStyle(fontSize: 14)),
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
        ),

        // Expense Summary
        Container(
          padding: const EdgeInsets.all(16),
          color: const Color(0xFFF8F9FA),
          child: Row(
            children: [
              Expanded(
                child: _buildExpenseSummaryCard(
                  'Total Expenses',
                  '₹${_formatAmount(_getTotalExpenses(filteredExpenses))}',
                  Icons.money_off,
                  const Color(0xFFFF5722),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildExpenseSummaryCard(
                  'Pending',
                  '₹${_formatAmount(_getPendingExpenses(filteredExpenses))}',
                  Icons.pending,
                  const Color(0xFFFF9800),
                ),
              ),
            ],
          ),
        ),

        // Expenses List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: filteredExpenses.length,
            itemBuilder: (context, index) {
              return _buildExpenseCard(filteredExpenses[index]);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildIncomeTab() {
    return Column(
      children: [
        // Income Summary
        Container(
          padding: const EdgeInsets.all(16),
          color: const Color(0xFFF8F9FA),
          child: Row(
            children: [
              Expanded(
                child: _buildIncomeSummaryCard(
                  'Total Income',
                  '₹${_formatAmount(_getTotalIncome())}',
                  Icons.attach_money,
                  const Color(0xFF4CAF50),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildIncomeSummaryCard(
                  'This Month',
                  '₹${_formatAmount(_getMonthlyIncome())}',
                  Icons.calendar_month,
                  const Color(0xFF2196F3),
                ),
              ),
            ],
          ),
        ),

        // Income List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: income.length,
            itemBuilder: (context, index) {
              return _buildIncomeCard(income[index]);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildReportsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '📊 Financial Reports',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 16),

          _buildReportOption(
            '💰 Budget vs Actual Report',
            'Compare budgeted vs actual expenses',
            Icons.compare_arrows,
            const Color(0xFF2196F3),
            () => _generateReport('Budget vs Actual'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '📈 Monthly Expense Trend',
            'Track monthly expense patterns',
            Icons.trending_up,
            const Color(0xFF4CAF50),
            () => _generateReport('Monthly Trend'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '🏷️ Category-wise Analysis',
            'Detailed breakdown by categories',
            Icons.pie_chart,
            const Color(0xFFFF9800),
            () => _generateReport('Category Analysis'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '💳 Payment Method Report',
            'Analysis by payment methods',
            Icons.payment,
            const Color(0xFF9C27B0),
            () => _generateReport('Payment Methods'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '📅 Annual Financial Summary',
            'Complete yearly financial overview',
            Icons.date_range,
            const Color(0xFF607D8B),
            () => _generateReport('Annual Summary'),
          ),
          const SizedBox(height: 20),

          // Quick Financial Stats
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF673AB7), Color(0xFF9C27B0)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '📊 Quick Financial Stats',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: _buildQuickStat('Avg Monthly Expense', '₹${_formatAmount(budgetData['totalExpenses'] / 12)}', '💸'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildQuickStat('Avg Monthly Income', '₹${_formatAmount(budgetData['totalIncome'] / 12)}', '💰'),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildQuickStat('Largest Expense', '₹${_formatAmount(_getLargestExpense())}', '📊'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildQuickStat('Budget Health', '${_getBudgetHealth()}%', '❤️'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF666666),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryExpenseCard(String category, double amount) {
    final percentage = (amount / budgetData['totalExpenses']) * 100;
    final color = _getCategoryColor(category);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              _getCategoryIcon(category),
              color: color,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  category,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
                Text(
                  '${percentage.toStringAsFixed(1)}% of total expenses',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
          Text(
            '₹${_formatAmount(amount)}',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildExpenseSummaryCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF666666),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildExpenseCard(Map<String, dynamic> expense) {
    final statusColor = expense['status'] == 'Paid' 
        ? const Color(0xFF4CAF50) 
        : const Color(0xFFFF9800);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _getCategoryColor(expense['category']).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  _getCategoryIcon(expense['category']),
                  color: _getCategoryColor(expense['category']),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      expense['title'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF333333),
                      ),
                    ),
                    Text(
                      '${expense['category']} • ${expense['date']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '₹${_formatAmount(expense['amount'])}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFFF5722),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: statusColor,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      expense['status'],
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            expense['description'],
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[700],
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: Text(
                  'Payment: ${expense['paymentMethod']}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ),
              Text(
                'Approved by: ${expense['approvedBy']}',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildIncomeSummaryCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF666666),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildIncomeCard(Map<String, dynamic> incomeItem) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 5,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFF4CAF50).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.attach_money,
                  color: Color(0xFF4CAF50),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      incomeItem['title'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF333333),
                      ),
                    ),
                    Text(
                      '${incomeItem['category']} • ${incomeItem['date']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '₹${_formatAmount(incomeItem['amount'])}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF4CAF50),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFF4CAF50),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      incomeItem['status'],
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            incomeItem['description'],
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[700],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Source: ${incomeItem['source']}',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReportOption(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.3)),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              spreadRadius: 1,
              blurRadius: 5,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios, color: Colors.grey[400], size: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickStat(String title, String value, String emoji) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        children: [
          Text(
            emoji,
            style: const TextStyle(fontSize: 20),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 10,
              color: Colors.white70,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  // Helper methods
  String _formatAmount(double amount) {
    if (amount >= 100000) {
      return '${(amount / 100000).toStringAsFixed(1)}L';
    } else if (amount >= 1000) {
      return '${(amount / 1000).toStringAsFixed(1)}K';
    }
    return amount.toStringAsFixed(0);
  }

  Color _getCategoryColor(String category) {
    final colors = {
      'Salaries': const Color(0xFF2196F3),
      'Infrastructure': const Color(0xFF4CAF50),
      'Utilities': const Color(0xFFFF9800),
      'Supplies': const Color(0xFF9C27B0),
      'Transportation': const Color(0xFF607D8B),
      'Events': const Color(0xFFE91E63),
      'Maintenance': const Color(0xFF795548),
      'Other': const Color(0xFF9E9E9E),
    };
    return colors[category] ?? const Color(0xFF9E9E9E);
  }

  IconData _getCategoryIcon(String category) {
    final icons = {
      'Salaries': Icons.people,
      'Infrastructure': Icons.business,
      'Utilities': Icons.electrical_services,
      'Supplies': Icons.inventory,
      'Transportation': Icons.directions_bus,
      'Events': Icons.event,
      'Maintenance': Icons.build,
      'Other': Icons.more_horiz,
    };
    return icons[category] ?? Icons.category;
  }

  Map<String, double> _getCategoryExpenses() {
    final categoryTotals = <String, double>{};
    for (final expense in expenses) {
      final category = expense['category'];
      categoryTotals[category] = (categoryTotals[category] ?? 0) + expense['amount'];
    }
    return categoryTotals;
  }

  double _getTotalExpenses(List<Map<String, dynamic>> expenseList) {
    return expenseList.fold(0.0, (sum, expense) => sum + expense['amount']);
  }

  double _getPendingExpenses(List<Map<String, dynamic>> expenseList) {
    return expenseList
        .where((expense) => expense['status'] == 'Pending')
        .fold(0.0, (sum, expense) => sum + expense['amount']);
  }

  double _getTotalIncome() {
    return income.fold(0.0, (sum, incomeItem) => sum + incomeItem['amount']);
  }

  double _getMonthlyIncome() {
    // For demo purposes, return current month income
    return income
        .where((incomeItem) => incomeItem['date'].startsWith('2024-01'))
        .fold(0.0, (sum, incomeItem) => sum + incomeItem['amount']);
  }

  double _getLargestExpense() {
    if (expenses.isEmpty) return 0.0;
    return expenses.map((e) => e['amount'] as double).reduce((a, b) => a > b ? a : b);
  }

  int _getBudgetHealth() {
    final remaining = budgetData['remainingBudget'] / budgetData['totalBudget'];
    return (remaining * 100).round();
  }

  void _showAddTransactionDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: const Row(
            children: [
              Icon(Icons.add_circle, color: Color(0xFF673AB7)),
              SizedBox(width: 8),
              Text('Add Transaction'),
            ],
          ),
          content: const Text('Add new expense or income functionality will be implemented here.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF673AB7),
                foregroundColor: Colors.white,
              ),
              child: const Text('Add'),
            ),
          ],
        );
      },
    );
  }

  void _generateReport(String reportType) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: Row(
            children: [
              const Icon(Icons.description, color: Color(0xFF673AB7)),
              const SizedBox(width: 8),
              Text('Generate $reportType Report'),
            ],
          ),
          content: Text('$reportType report generated successfully!'),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF673AB7),
                foregroundColor: Colors.white,
              ),
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }
}