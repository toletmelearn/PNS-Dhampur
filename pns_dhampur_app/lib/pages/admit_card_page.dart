import 'package:flutter/material.dart';

class AdmitCardPage extends StatefulWidget {
  final String token;

  const AdmitCardPage({Key? key, required this.token}) : super(key: key);

  @override
  _AdmitCardPageState createState() => _AdmitCardPageState();
}

class _AdmitCardPageState extends State<AdmitCardPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedExam = 'All Exams';

  // Sample data for admit cards
  final List<Map<String, dynamic>> _admitCards = [
    {
      'id': 1,
      'studentName': 'Aarav Sharma',
      'rollNumber': '2024001',
      'class': 'Class 10',
      'section': 'A',
      'examType': 'Annual Exam',
      'examDate': '2024-03-15',
      'subjects': ['Mathematics', 'Science', 'English', 'Hindi', 'Social Studies'],
      'photo': '👦',
      'fatherName': 'Mr. Rajesh Sharma',
      'motherName': 'Mrs. Priya Sharma',
      'dob': '2009-05-15',
      'admissionNo': 'PNS2024001',
      'status': 'Generated',
    },
    {
      'id': 2,
      'studentName': 'Priya Verma',
      'rollNumber': '2024002',
      'class': 'Class 10',
      'section': 'A',
      'examType': 'Annual Exam',
      'examDate': '2024-03-15',
      'subjects': ['Mathematics', 'Science', 'English', 'Hindi', 'Social Studies'],
      'photo': '👧',
      'fatherName': 'Mr. Suresh Verma',
      'motherName': 'Mrs. Anita Verma',
      'dob': '2009-08-22',
      'admissionNo': 'PNS2024002',
      'status': 'Generated',
    },
    {
      'id': 3,
      'studentName': 'Arjun Kumar',
      'rollNumber': '2024003',
      'class': 'Class 9',
      'section': 'B',
      'examType': 'Half Yearly',
      'examDate': '2024-10-20',
      'subjects': ['Mathematics', 'Science', 'English', 'Hindi'],
      'photo': '👦',
      'fatherName': 'Mr. Vikash Kumar',
      'motherName': 'Mrs. Sunita Kumar',
      'dob': '2010-02-10',
      'admissionNo': 'PNS2024003',
      'status': 'Pending',
    },
  ];

  final List<String> _classes = ['All Classes', 'Class 10', 'Class 9', 'Class 8', 'Class 7', 'Class 6'];
  final List<String> _exams = ['All Exams', 'Annual Exam', 'Half Yearly', 'Unit Test', 'Monthly Test'];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Admit Card Generation',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: const Color(0xFF3F51B5),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Generate', icon: Icon(Icons.card_membership)),
            Tab(text: 'Preview', icon: Icon(Icons.preview)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildGenerateTab(),
          _buildPreviewTab(),
          _buildReportsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showBulkGenerateDialog(),
        backgroundColor: const Color(0xFF3F51B5),
        child: const Icon(Icons.add_card, color: Colors.white),
      ),
    );
  }

  Widget _buildGenerateTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF3F51B5), Color(0xFF5C6BC0)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '🎫 Admit Card Generator',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Generate exam admit cards for students! 📝✨',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 16,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Filter Section
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 1,
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Filter Options',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2C3E50),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Class',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF7F8C8D),
                            ),
                          ),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            value: _selectedClass,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            ),
                            items: _classes.map((String value) {
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
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Exam Type',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF7F8C8D),
                            ),
                          ),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            value: _selectedExam,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            ),
                            items: _exams.map((String value) {
                              return DropdownMenuItem<String>(
                                value: value,
                                child: Text(value),
                              );
                            }).toList(),
                            onChanged: (String? newValue) {
                              setState(() {
                                _selectedExam = newValue!;
                              });
                            },
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Statistics Cards
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Total Cards',
                  '${_getFilteredCards().length}',
                  Icons.card_membership,
                  const Color(0xFF4CAF50),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard(
                  'Generated',
                  '${_getFilteredCards().where((card) => card['status'] == 'Generated').length}',
                  Icons.check_circle,
                  const Color(0xFF2196F3),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Pending',
                  '${_getFilteredCards().where((card) => card['status'] == 'Pending').length}',
                  Icons.pending,
                  const Color(0xFFFF9800),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard(
                  'Classes',
                  '${_getUniqueClasses().length}',
                  Icons.school,
                  const Color(0xFF9C27B0),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Student List
          const Text(
            'Student Admit Cards',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 16),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _getFilteredCards().length,
            itemBuilder: (context, index) {
              final card = _getFilteredCards()[index];
              return _buildAdmitCardItem(card);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildPreviewTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Admit Card Preview',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 16),
          if (_admitCards.isNotEmpty) _buildAdmitCardPreview(_admitCards.first),
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
            'Admit Card Reports',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 16),
          _buildReportCard(
            '📊 Generation Summary',
            'View admit card generation statistics',
            Icons.bar_chart,
            const Color(0xFF4CAF50),
            () => _generateReport('Generation Summary'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '📋 Class-wise Report',
            'Admit cards by class and section',
            Icons.class_,
            const Color(0xFF2196F3),
            () => _generateReport('Class-wise Report'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '📅 Exam Schedule',
            'Upcoming exam dates and subjects',
            Icons.schedule,
            const Color(0xFFFF9800),
            () => _generateReport('Exam Schedule'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '🎯 Status Report',
            'Generated vs pending admit cards',
            Icons.track_changes,
            const Color(0xFF9C27B0),
            () => _generateReport('Status Report'),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: color, size: 24),
              Text(
                value,
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              color: Color(0xFF7F8C8D),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAdmitCardItem(Map<String, dynamic> card) {
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
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: const Color(0xFF3F51B5).withOpacity(0.1),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Center(
              child: Text(
                card['photo'],
                style: const TextStyle(fontSize: 24),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  card['studentName'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2C3E50),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${card['class']} - ${card['section']} | Roll: ${card['rollNumber']}',
                  style: const TextStyle(
                    fontSize: 14,
                    color: Color(0xFF7F8C8D),
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(
                      card['status'] == 'Generated' ? Icons.check_circle : Icons.pending,
                      size: 16,
                      color: card['status'] == 'Generated' 
                          ? const Color(0xFF4CAF50) 
                          : const Color(0xFFFF9800),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      card['status'],
                      style: TextStyle(
                        fontSize: 12,
                        color: card['status'] == 'Generated' 
                            ? const Color(0xFF4CAF50) 
                            : const Color(0xFFFF9800),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Column(
            children: [
              IconButton(
                onPressed: () => _previewAdmitCard(card),
                icon: const Icon(Icons.preview, color: Color(0xFF3F51B5)),
                tooltip: 'Preview',
              ),
              IconButton(
                onPressed: () => _generateAdmitCard(card),
                icon: const Icon(Icons.print, color: Color(0xFF4CAF50)),
                tooltip: 'Generate',
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAdmitCardPreview(Map<String, dynamic> card) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF3F51B5),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              children: [
                const Text(
                  'PNS DHAMPUR SCHOOL',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'ADMIT CARD',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  card['examType'],
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Student Details
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 80,
                height: 100,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Text(
                    card['photo'],
                    style: const TextStyle(fontSize: 40),
                  ),
                ),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildDetailRow('Student Name', card['studentName']),
                    _buildDetailRow('Father\'s Name', card['fatherName']),
                    _buildDetailRow('Mother\'s Name', card['motherName']),
                    _buildDetailRow('Date of Birth', card['dob']),
                    _buildDetailRow('Admission No.', card['admissionNo']),
                    _buildDetailRow('Roll Number', card['rollNumber']),
                    _buildDetailRow('Class & Section', '${card['class']} - ${card['section']}'),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Subjects
          const Text(
            'Subjects:',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            children: (card['subjects'] as List<String>).map((subject) {
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFF3F51B5).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  subject,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF3F51B5),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),

          // Instructions
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Instructions:',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2C3E50),
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  '• Bring this admit card to the examination hall\n• Report 30 minutes before exam time\n• Carry valid ID proof\n• Mobile phones are not allowed',
                  style: TextStyle(
                    fontSize: 12,
                    color: Color(0xFF7F8C8D),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: Color(0xFF7F8C8D),
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF2C3E50),
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReportCard(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Row(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(25),
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
                      color: Color(0xFF2C3E50),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF7F8C8D),
                    ),
                  ),
                ],
              ),
            ),
            const Icon(
              Icons.arrow_forward_ios,
              size: 16,
              color: Color(0xFF7F8C8D),
            ),
          ],
        ),
      ),
    );
  }

  List<Map<String, dynamic>> _getFilteredCards() {
    return _admitCards.where((card) {
      bool classMatch = _selectedClass == 'All Classes' || card['class'] == _selectedClass;
      bool examMatch = _selectedExam == 'All Exams' || card['examType'] == _selectedExam;
      return classMatch && examMatch;
    }).toList();
  }

  List<String> _getUniqueClasses() {
    return _admitCards.map((card) => card['class'] as String).toSet().toList();
  }

  void _showBulkGenerateDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Bulk Generate Admit Cards'),
          content: const Text('Generate admit cards for all students in selected class and exam?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                _bulkGenerateCards();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF3F51B5),
              ),
              child: const Text('Generate', style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _previewAdmitCard(Map<String, dynamic> card) {
    _tabController.animateTo(1);
  }

  void _generateAdmitCard(Map<String, dynamic> card) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating admit card for ${card['studentName']}...')),
    );
  }

  void _bulkGenerateCards() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Bulk generating admit cards...')),
    );
  }

  void _generateReport(String reportType) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating $reportType report...')),
    );
  }
}