import 'package:flutter/material.dart';

class InventoryManagementPage extends StatefulWidget {
  final String token;

  const InventoryManagementPage({Key? key, required this.token}) : super(key: key);

  @override
  _InventoryManagementPageState createState() => _InventoryManagementPageState();
}

class _InventoryManagementPageState extends State<InventoryManagementPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String selectedCategory = 'All Categories';
  String searchQuery = '';

  final List<String> categories = [
    'All Categories', 'Stationery', 'Books', 'Sports Equipment', 
    'Electronics', 'Furniture', 'Cleaning Supplies', 'Medical Supplies'
  ];

  // Sample inventory data
  final List<Map<String, dynamic>> inventoryItems = [
    {
      'id': 1,
      'name': 'Whiteboard Markers',
      'category': 'Stationery',
      'quantity': 45,
      'minStock': 20,
      'maxStock': 100,
      'unit': 'pieces',
      'price': 25.0,
      'supplier': 'ABC Stationery',
      'lastUpdated': '2024-01-15',
      'status': 'In Stock',
      'location': 'Store Room A',
    },
    {
      'id': 2,
      'name': 'Mathematics Textbooks',
      'category': 'Books',
      'quantity': 8,
      'minStock': 15,
      'maxStock': 50,
      'unit': 'books',
      'price': 150.0,
      'supplier': 'Educational Publishers',
      'lastUpdated': '2024-01-14',
      'status': 'Low Stock',
      'location': 'Library',
    },
    {
      'id': 3,
      'name': 'Football',
      'category': 'Sports Equipment',
      'quantity': 12,
      'minStock': 5,
      'maxStock': 20,
      'unit': 'pieces',
      'price': 800.0,
      'supplier': 'Sports World',
      'lastUpdated': '2024-01-13',
      'status': 'In Stock',
      'location': 'Sports Room',
    },
    {
      'id': 4,
      'name': 'Projector',
      'category': 'Electronics',
      'quantity': 2,
      'minStock': 2,
      'maxStock': 5,
      'unit': 'pieces',
      'price': 25000.0,
      'supplier': 'Tech Solutions',
      'lastUpdated': '2024-01-12',
      'status': 'Critical',
      'location': 'AV Room',
    },
    {
      'id': 5,
      'name': 'Student Desks',
      'category': 'Furniture',
      'quantity': 85,
      'minStock': 50,
      'maxStock': 150,
      'unit': 'pieces',
      'price': 1200.0,
      'supplier': 'Furniture Plus',
      'lastUpdated': '2024-01-11',
      'status': 'In Stock',
      'location': 'Classrooms',
    },
  ];

  // Sample transaction data
  final List<Map<String, dynamic>> transactions = [
    {
      'id': 1,
      'type': 'IN',
      'item': 'Whiteboard Markers',
      'quantity': 20,
      'date': '2024-01-15',
      'reference': 'PO-2024-001',
      'notes': 'Monthly stock replenishment',
    },
    {
      'id': 2,
      'type': 'OUT',
      'item': 'Mathematics Textbooks',
      'quantity': 7,
      'date': '2024-01-14',
      'reference': 'REQ-2024-005',
      'notes': 'Distributed to Class 5A',
    },
    {
      'id': 3,
      'type': 'IN',
      'item': 'Football',
      'quantity': 5,
      'date': '2024-01-13',
      'reference': 'PO-2024-002',
      'notes': 'Sports equipment purchase',
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
          'Inventory Management 📦',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Colors.white,
          ),
        ),
        backgroundColor: const Color(0xFF607D8B),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Items', icon: Icon(Icons.inventory, size: 20)),
            Tab(text: 'Categories', icon: Icon(Icons.category, size: 20)),
            Tab(text: 'Transactions', icon: Icon(Icons.swap_horiz, size: 20)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics, size: 20)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildItemsTab(),
          _buildCategoriesTab(),
          _buildTransactionsTab(),
          _buildReportsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddItemDialog(),
        backgroundColor: const Color(0xFF607D8B),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildItemsTab() {
    final filteredItems = inventoryItems.where((item) {
      final matchesCategory = selectedCategory == 'All Categories' || 
                             item['category'] == selectedCategory;
      final matchesSearch = searchQuery.isEmpty ||
                           item['name'].toLowerCase().contains(searchQuery.toLowerCase());
      return matchesCategory && matchesSearch;
    }).toList();

    return Column(
      children: [
        // Search and Filter Section
        Container(
          padding: const EdgeInsets.all(16),
          color: Colors.white,
          child: Column(
            children: [
              // Search Bar
              TextField(
                onChanged: (value) => setState(() => searchQuery = value),
                decoration: InputDecoration(
                  hintText: 'Search items...',
                  prefixIcon: const Icon(Icons.search, color: Color(0xFF607D8B)),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(color: Colors.grey.withOpacity(0.3)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: const BorderSide(color: Color(0xFF607D8B)),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              // Category Filter
              SizedBox(
                height: 40,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: categories.length,
                  itemBuilder: (context, index) {
                    final category = categories[index];
                    final isSelected = selectedCategory == category;
                    return Container(
                      margin: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: Text(category),
                        selected: isSelected,
                        onSelected: (selected) {
                          setState(() => selectedCategory = category);
                        },
                        backgroundColor: Colors.grey[200],
                        selectedColor: const Color(0xFF607D8B),
                        labelStyle: TextStyle(
                          color: isSelected ? Colors.white : Colors.black87,
                          fontSize: 12,
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),

        // Inventory Summary Cards
        Container(
          height: 100,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Row(
            children: [
              Expanded(child: _buildSummaryCard('Total Items', '${inventoryItems.length}', Icons.inventory, const Color(0xFF2196F3))),
              const SizedBox(width: 8),
              Expanded(child: _buildSummaryCard('Low Stock', '${_getLowStockCount()}', Icons.warning, const Color(0xFFFF9800))),
              const SizedBox(width: 8),
              Expanded(child: _buildSummaryCard('Out of Stock', '${_getOutOfStockCount()}', Icons.error, const Color(0xFFF44336))),
            ],
          ),
        ),

        // Items List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: filteredItems.length,
            itemBuilder: (context, index) {
              return _buildInventoryItemCard(filteredItems[index]);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildCategoriesTab() {
    final categoryStats = _getCategoryStats();
    
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '📂 Inventory Categories',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 16),
          
          ...categoryStats.entries.map((entry) => _buildCategoryCard(entry.key, entry.value)).toList(),
        ],
      ),
    );
  }

  Widget _buildTransactionsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Transaction Summary
          Row(
            children: [
              Expanded(
                child: _buildTransactionSummaryCard(
                  'Stock In',
                  '${_getTransactionCount("IN")}',
                  Icons.arrow_downward,
                  const Color(0xFF4CAF50),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildTransactionSummaryCard(
                  'Stock Out',
                  '${_getTransactionCount("OUT")}',
                  Icons.arrow_upward,
                  const Color(0xFFFF5722),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          const Text(
            '📋 Recent Transactions',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 12),
          
          ...transactions.map((transaction) => _buildTransactionCard(transaction)).toList(),
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
          // Report Options
          const Text(
            '📊 Inventory Reports',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 16),

          _buildReportOption(
            '📈 Stock Level Report',
            'Current stock levels and alerts',
            Icons.trending_up,
            const Color(0xFF2196F3),
            () => _generateReport('Stock Level'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '💰 Inventory Valuation',
            'Total inventory value by category',
            Icons.attach_money,
            const Color(0xFF4CAF50),
            () => _generateReport('Valuation'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '📋 Transaction History',
            'Complete transaction log',
            Icons.history,
            const Color(0xFFFF9800),
            () => _generateReport('Transaction History'),
          ),
          const SizedBox(height: 12),
          _buildReportOption(
            '⚠️ Low Stock Alert',
            'Items below minimum stock level',
            Icons.warning,
            const Color(0xFFF44336),
            () => _generateReport('Low Stock'),
          ),
          const SizedBox(height: 20),

          // Quick Stats
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF607D8B), Color(0xFF90A4AE)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '📊 Quick Statistics',
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
                      child: _buildQuickStat('Total Value', '₹${_getTotalInventoryValue()}', '💰'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildQuickStat('Categories', '${categories.length - 1}', '📂'),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildQuickStat('Avg. Item Value', '₹${_getAverageItemValue()}', '📊'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildQuickStat('Last Updated', 'Today', '🕒'),
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
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 3,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 10,
              color: Color(0xFF666666),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildInventoryItemCard(Map<String, dynamic> item) {
    final status = _getItemStatus(item);
    final statusColor = _getStatusColor(status);

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
                  color: _getCategoryColor(item['category']).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  _getCategoryIcon(item['category']),
                  color: _getCategoryColor(item['category']),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['name'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF333333),
                      ),
                    ),
                    Text(
                      '${item['category']} • ${item['location']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  status,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildItemInfo('Quantity', '${item['quantity']} ${item['unit']}'),
              ),
              Expanded(
                child: _buildItemInfo('Min Stock', '${item['minStock']} ${item['unit']}'),
              ),
              Expanded(
                child: _buildItemInfo('Price', '₹${item['price']}'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _buildItemInfo('Supplier', item['supplier']),
              ),
              Expanded(
                child: _buildItemInfo('Updated', item['lastUpdated']),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildItemInfo(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Color(0xFF333333),
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 10,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }

  Widget _buildCategoryCard(String category, Map<String, dynamic> stats) {
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
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: _getCategoryColor(category).withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              _getCategoryIcon(category),
              color: _getCategoryColor(category),
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  category,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
                Text(
                  '${stats['count']} items • ₹${stats['value']} total value',
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
                '${stats['count']}',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: _getCategoryColor(category),
                ),
              ),
              const Text(
                'Items',
                style: TextStyle(
                  fontSize: 10,
                  color: Color(0xFF666666),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionSummaryCard(String title, String value, IconData icon, Color color) {
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
              fontSize: 24,
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
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionCard(Map<String, dynamic> transaction) {
    final isIncoming = transaction['type'] == 'IN';
    final color = isIncoming ? const Color(0xFF4CAF50) : const Color(0xFFFF5722);
    final icon = isIncoming ? Icons.arrow_downward : Icons.arrow_upward;

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
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  transaction['item'],
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
                Text(
                  '${transaction['reference']} • ${transaction['date']}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
                if (transaction['notes'].isNotEmpty)
                  Text(
                    transaction['notes'],
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey[500],
                      fontStyle: FontStyle.italic,
                    ),
                  ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${isIncoming ? '+' : '-'}${transaction['quantity']}',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
              Text(
                transaction['type'],
                style: TextStyle(
                  fontSize: 10,
                  color: color,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
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
              fontSize: 16,
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
  String _getItemStatus(Map<String, dynamic> item) {
    if (item['quantity'] == 0) return 'Out of Stock';
    if (item['quantity'] <= item['minStock']) return 'Low Stock';
    if (item['quantity'] <= item['minStock'] * 1.2) return 'Critical';
    return 'In Stock';
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'In Stock': return const Color(0xFF4CAF50);
      case 'Low Stock': return const Color(0xFFFF9800);
      case 'Critical': return const Color(0xFFFF5722);
      case 'Out of Stock': return const Color(0xFFF44336);
      default: return const Color(0xFF9E9E9E);
    }
  }

  Color _getCategoryColor(String category) {
    final colors = {
      'Stationery': const Color(0xFF2196F3),
      'Books': const Color(0xFF4CAF50),
      'Sports Equipment': const Color(0xFFFF9800),
      'Electronics': const Color(0xFF9C27B0),
      'Furniture': const Color(0xFF795548),
      'Cleaning Supplies': const Color(0xFF607D8B),
      'Medical Supplies': const Color(0xFFF44336),
    };
    return colors[category] ?? const Color(0xFF9E9E9E);
  }

  IconData _getCategoryIcon(String category) {
    final icons = {
      'Stationery': Icons.edit,
      'Books': Icons.book,
      'Sports Equipment': Icons.sports,
      'Electronics': Icons.devices,
      'Furniture': Icons.chair,
      'Cleaning Supplies': Icons.cleaning_services,
      'Medical Supplies': Icons.medical_services,
    };
    return icons[category] ?? Icons.inventory;
  }

  int _getLowStockCount() {
    return inventoryItems.where((item) => item['quantity'] <= item['minStock']).length;
  }

  int _getOutOfStockCount() {
    return inventoryItems.where((item) => item['quantity'] == 0).length;
  }

  Map<String, Map<String, dynamic>> _getCategoryStats() {
    final stats = <String, Map<String, dynamic>>{};
    
    for (final item in inventoryItems) {
      final category = item['category'];
      if (!stats.containsKey(category)) {
        stats[category] = {'count': 0, 'value': 0.0};
      }
      stats[category]!['count']++;
      stats[category]!['value'] += (item['quantity'] * item['price']);
    }
    
    return stats;
  }

  int _getTransactionCount(String type) {
    return transactions.where((t) => t['type'] == type).length;
  }

  double _getTotalInventoryValue() {
    return inventoryItems.fold(0.0, (sum, item) => sum + (item['quantity'] * item['price']));
  }

  double _getAverageItemValue() {
    if (inventoryItems.isEmpty) return 0.0;
    return _getTotalInventoryValue() / inventoryItems.length;
  }

  void _showAddItemDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: const Row(
            children: [
              Icon(Icons.add_box, color: Color(0xFF607D8B)),
              SizedBox(width: 8),
              Text('Add New Item'),
            ],
          ),
          content: const Text('Add new item functionality will be implemented here.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF607D8B),
                foregroundColor: Colors.white,
              ),
              child: const Text('Add Item'),
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
              const Icon(Icons.description, color: Color(0xFF607D8B)),
              const SizedBox(width: 8),
              Text('Generate $reportType Report'),
            ],
          ),
          content: Text('$reportType report generated successfully!'),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF607D8B),
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