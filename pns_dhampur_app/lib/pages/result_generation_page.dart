import 'package:flutter/material.dart';

class ResultGenerationPage extends StatefulWidget {
  final String token;

  const ResultGenerationPage({Key? key, required this.token}) : super(key: key);

  @override
  _ResultGenerationPageState createState() => _ResultGenerationPageState();
}

class _ResultGenerationPageState extends State<ResultGenerationPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String selectedClass = 'Class 1';
  String selectedSubject = 'Mathematics';
  String selectedExam = 'Mid Term';

  final List<String> classes = [
    'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5',
    'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'
  ];

  final List<String> subjects = [
    'Mathematics', 'English', 'Hindi', 'Science', 'Social Studies',
    'Computer Science', 'Physical Education', 'Art & Craft'
  ];

  final List<String> examTypes = [
    'Mid Term', 'Final Term', 'Unit Test 1', 'Unit Test 2', 'Annual Exam'
  ];

  // Sample student data
  final List<Map<String, dynamic>> students = [
    {
      'id': 1,
      'name': 'Aarav Sharma',
      'rollNo': '001',
      'class': 'Class 1',
      'subjects': {
        'Mathematics': {'marks': 85, 'grade': 'A', 'maxMarks': 100},
        'English': {'marks': 78, 'grade': 'B+', 'maxMarks': 100},
        'Hindi': {'marks': 92, 'grade': 'A+', 'maxMarks': 100},
        'Science': {'marks': 88, 'grade': 'A', 'maxMarks': 100},
      },
      'totalMarks': 343,
      'percentage': 85.75,
      'grade': 'A',
      'rank': 2
    },
    {
      'id': 2,
      'name': 'Priya Patel',
      'rollNo': '002',
      'class': 'Class 1',
      'subjects': {
        'Mathematics': {'marks': 95, 'grade': 'A+', 'maxMarks': 100},
        'English': {'marks': 89, 'grade': 'A', 'maxMarks': 100},
        'Hindi': {'marks': 94, 'grade': 'A+', 'maxMarks': 100},
        'Science': {'marks': 91, 'grade': 'A+', 'maxMarks': 100},
      },
      'totalMarks': 369,
      'percentage': 92.25,
      'grade': 'A+',
      'rank': 1
    },
    {
      'id': 3,
      'name': 'Arjun Singh',
      'rollNo': '003',
      'class': 'Class 1',
      'subjects': {
        'Mathematics': {'marks': 72, 'grade': 'B', 'maxMarks': 100},
        'English': {'marks': 68, 'grade': 'B', 'maxMarks': 100},
        'Hindi': {'marks': 75, 'grade': 'B+', 'maxMarks': 100},
        'Science': {'marks': 70, 'grade': 'B', 'maxMarks': 100},
      },
      'totalMarks': 285,
      'percentage': 71.25,
      'grade': 'B',
      'rank': 3
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
          'Result Generation 📊',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Colors.white,
          ),
        ),
        backgroundColor: const Color(0xFF2196F3),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Results', icon: Icon(Icons.assessment, size: 20)),
            Tab(text: 'Generate', icon: Icon(Icons.auto_awesome, size: 20)),
            Tab(text: 'Reports', icon: Icon(Icons.description, size: 20)),
            Tab(text: 'Analytics', icon: Icon(Icons.analytics, size: 20)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildResultsTab(),
          _buildGenerateTab(),
          _buildReportsTab(),
          _buildAnalyticsTab(),
        ],
      ),
    );
  }

  Widget _buildResultsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
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
                  blurRadius: 5,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '🔍 Filter Results',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2196F3),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: _buildDropdown(
                        'Class',
                        selectedClass,
                        classes,
                        (value) => setState(() => selectedClass = value!),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildDropdown(
                        'Exam',
                        selectedExam,
                        examTypes,
                        (value) => setState(() => selectedExam = value!),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Results List
          const Text(
            '📋 Student Results',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 12),
          
          ...students.map((student) => _buildStudentResultCard(student)).toList(),
        ],
      ),
    );
  }

  Widget _buildGenerateTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Generate Options
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF4CAF50), Color(0xFF8BC34A)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '✨ Auto Generate Results',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Generate results automatically based on marks',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white70,
                  ),
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: _buildDropdown(
                        'Select Class',
                        selectedClass,
                        classes,
                        (value) => setState(() => selectedClass = value!),
                        isWhite: true,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildDropdown(
                        'Select Exam',
                        selectedExam,
                        examTypes,
                        (value) => setState(() => selectedExam = value!),
                        isWhite: true,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Generation Options
          _buildGenerationOption(
            '📊 Generate Class Results',
            'Generate results for entire class',
            Icons.class_,
            const Color(0xFF2196F3),
            () => _showGenerationDialog('Class Results'),
          ),
          const SizedBox(height: 12),
          _buildGenerationOption(
            '📄 Generate Report Cards',
            'Create individual report cards',
            Icons.description,
            const Color(0xFFFF9800),
            () => _showGenerationDialog('Report Cards'),
          ),
          const SizedBox(height: 12),
          _buildGenerationOption(
            '📈 Generate Analytics',
            'Create performance analytics',
            Icons.analytics,
            const Color(0xFF9C27B0),
            () => _showGenerationDialog('Analytics'),
          ),
          const SizedBox(height: 12),
          _buildGenerationOption(
            '📋 Generate Merit List',
            'Create class merit list',
            Icons.emoji_events,
            const Color(0xFFFF5722),
            () => _showGenerationDialog('Merit List'),
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
            '📄 Generated Reports',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 16),

          // Report Cards
          _buildReportSection(
            '📋 Report Cards',
            'Individual student report cards',
            [
              {'name': 'Class 1 - Mid Term Reports', 'date': '2024-01-15', 'students': 25},
              {'name': 'Class 2 - Final Term Reports', 'date': '2024-01-10', 'students': 28},
              {'name': 'Class 3 - Unit Test Reports', 'date': '2024-01-05', 'students': 30},
            ],
          ),
          const SizedBox(height: 20),

          // Merit Lists
          _buildReportSection(
            '🏆 Merit Lists',
            'Class-wise merit lists',
            [
              {'name': 'Class 1 - Annual Merit List', 'date': '2024-01-12', 'students': 25},
              {'name': 'Class 2 - Mid Term Merit List', 'date': '2024-01-08', 'students': 28},
            ],
          ),
          const SizedBox(height: 20),

          // Analytics Reports
          _buildReportSection(
            '📊 Analytics Reports',
            'Performance analysis reports',
            [
              {'name': 'Subject-wise Performance', 'date': '2024-01-14', 'students': 150},
              {'name': 'Class Comparison Report', 'date': '2024-01-11', 'students': 150},
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAnalyticsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Performance Overview
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
                  '📈 Performance Overview',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: _buildAnalyticsCard('Average Score', '78.5%', '📊'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildAnalyticsCard('Pass Rate', '92.3%', '✅'),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildAnalyticsCard('Top Performer', 'Priya P.', '🏆'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildAnalyticsCard('Total Students', '150', '👥'),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Subject Performance
          const Text(
            '📚 Subject Performance',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF333333),
            ),
          ),
          const SizedBox(height: 12),
          
          ...subjects.take(4).map((subject) => _buildSubjectPerformanceCard(subject)).toList(),
        ],
      ),
    );
  }

  Widget _buildStudentResultCard(Map<String, dynamic> student) {
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
              CircleAvatar(
                backgroundColor: _getGradeColor(student['grade']),
                child: Text(
                  '#${student['rank']}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
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
                        color: Color(0xFF333333),
                      ),
                    ),
                    Text(
                      'Roll No: ${student['rollNo']} • ${student['class']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: _getGradeColor(student['grade']),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${student['percentage'].toStringAsFixed(1)}% (${student['grade']})',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildResultInfo('Total Marks', '${student['totalMarks']}/400'),
              ),
              Expanded(
                child: _buildResultInfo('Percentage', '${student['percentage']}%'),
              ),
              Expanded(
                child: _buildResultInfo('Class Rank', '#${student['rank']}'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildResultInfo(String label, String value) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Color(0xFF2196F3),
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }

  Widget _buildGenerationOption(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
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

  Widget _buildReportSection(String title, String subtitle, List<Map<String, dynamic>> reports) {
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
          const SizedBox(height: 12),
          ...reports.map((report) => _buildReportItem(report)).toList(),
        ],
      ),
    );
  }

  Widget _buildReportItem(Map<String, dynamic> report) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FA),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  report['name'],
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF333333),
                  ),
                ),
                Text(
                  'Generated: ${report['date']} • ${report['students']} students',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => _showReportOptions(report['name']),
            icon: const Icon(Icons.more_vert, color: Color(0xFF2196F3)),
          ),
        ],
      ),
    );
  }

  Widget _buildAnalyticsCard(String title, String value, String emoji) {
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
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              color: Colors.white70,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSubjectPerformanceCard(String subject) {
    final random = [85.2, 78.9, 92.1, 76.5, 88.3, 81.7, 79.4, 86.8];
    final performance = random[subjects.indexOf(subject) % random.length];
    
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
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: _getSubjectColor(subject).withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              _getSubjectIcon(subject),
              color: _getSubjectColor(subject),
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  subject,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
                Text(
                  'Class Average: ${performance.toStringAsFixed(1)}%',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: _getPerformanceColor(performance),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              _getPerformanceGrade(performance),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown(String label, String value, List<String> items, ValueChanged<String?> onChanged, {bool isWhite = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: isWhite ? Colors.white : const Color(0xFF666666),
          ),
        ),
        const SizedBox(height: 4),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(
            color: isWhite ? Colors.white : const Color(0xFFF8F9FA),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey.withOpacity(0.3)),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: value,
              isExpanded: true,
              onChanged: onChanged,
              style: TextStyle(
                fontSize: 14,
                color: isWhite ? const Color(0xFF333333) : const Color(0xFF333333),
              ),
              items: items.map((String item) {
                return DropdownMenuItem<String>(
                  value: item,
                  child: Text(item),
                );
              }).toList(),
            ),
          ),
        ),
      ],
    );
  }

  Color _getGradeColor(String grade) {
    switch (grade) {
      case 'A+': return const Color(0xFF4CAF50);
      case 'A': return const Color(0xFF8BC34A);
      case 'B+': return const Color(0xFFFF9800);
      case 'B': return const Color(0xFFFF5722);
      case 'C': return const Color(0xFFF44336);
      default: return const Color(0xFF9E9E9E);
    }
  }

  Color _getSubjectColor(String subject) {
    final colors = [
      const Color(0xFF2196F3), const Color(0xFF4CAF50), const Color(0xFFFF9800),
      const Color(0xFF9C27B0), const Color(0xFFFF5722), const Color(0xFF607D8B),
      const Color(0xFF795548), const Color(0xFFE91E63)
    ];
    return colors[subjects.indexOf(subject) % colors.length];
  }

  IconData _getSubjectIcon(String subject) {
    switch (subject) {
      case 'Mathematics': return Icons.calculate;
      case 'English': return Icons.language;
      case 'Hindi': return Icons.translate;
      case 'Science': return Icons.science;
      case 'Social Studies': return Icons.public;
      case 'Computer Science': return Icons.computer;
      case 'Physical Education': return Icons.sports;
      case 'Art & Craft': return Icons.palette;
      default: return Icons.book;
    }
  }

  Color _getPerformanceColor(double performance) {
    if (performance >= 90) return const Color(0xFF4CAF50);
    if (performance >= 80) return const Color(0xFF8BC34A);
    if (performance >= 70) return const Color(0xFFFF9800);
    if (performance >= 60) return const Color(0xFFFF5722);
    return const Color(0xFFF44336);
  }

  String _getPerformanceGrade(double performance) {
    if (performance >= 90) return 'A+';
    if (performance >= 80) return 'A';
    if (performance >= 70) return 'B+';
    if (performance >= 60) return 'B';
    return 'C';
  }

  void _showGenerationDialog(String type) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: Row(
            children: [
              const Icon(Icons.auto_awesome, color: Color(0xFF4CAF50)),
              const SizedBox(width: 8),
              Text('Generate $type'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Generate $type for $selectedClass - $selectedExam?'),
              const SizedBox(height: 16),
              const Text(
                'This will automatically:',
                style: TextStyle(fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 8),
              const Text('• Calculate grades and percentages'),
              const Text('• Generate class rankings'),
              const Text('• Create downloadable reports'),
              const Text('• Send notifications to parents'),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                _showSuccessDialog(type);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4CAF50),
                foregroundColor: Colors.white,
              ),
              child: const Text('Generate'),
            ),
          ],
        );
      },
    );
  }

  void _showSuccessDialog(String type) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: Color(0xFF4CAF50)),
              SizedBox(width: 8),
              Text('Success!'),
            ],
          ),
          content: Text('$type generated successfully for $selectedClass - $selectedExam!'),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4CAF50),
                foregroundColor: Colors.white,
              ),
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }

  void _showReportOptions(String reportName) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (BuildContext context) {
        return Container(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                reportName,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 20),
              ListTile(
                leading: const Icon(Icons.visibility, color: Color(0xFF2196F3)),
                title: const Text('View Report'),
                onTap: () => Navigator.pop(context),
              ),
              ListTile(
                leading: const Icon(Icons.download, color: Color(0xFF4CAF50)),
                title: const Text('Download PDF'),
                onTap: () => Navigator.pop(context),
              ),
              ListTile(
                leading: const Icon(Icons.share, color: Color(0xFFFF9800)),
                title: const Text('Share Report'),
                onTap: () => Navigator.pop(context),
              ),
              ListTile(
                leading: const Icon(Icons.print, color: Color(0xFF9C27B0)),
                title: const Text('Print Report'),
                onTap: () => Navigator.pop(context),
              ),
            ],
          ),
        );
      },
    );
  }
}