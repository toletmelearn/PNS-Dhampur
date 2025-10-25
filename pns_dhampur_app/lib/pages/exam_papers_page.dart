import 'package:flutter/material.dart';

class ExamPapersPage extends StatefulWidget {
  final String token;
  const ExamPapersPage({Key? key, required this.token}) : super(key: key);

  @override
  _ExamPapersPageState createState() => _ExamPapersPageState();
}

class _ExamPapersPageState extends State<ExamPapersPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedSubject = 'All Subjects';
  String _selectedExamType = 'All Types';

  // Sample data for exam papers
  final List<Map<String, dynamic>> _examPapers = [
    {
      'id': 'EP001',
      'title': 'Mathematics Mid-Term Exam',
      'class': 'Class 10',
      'subject': 'Mathematics',
      'examType': 'Mid-Term',
      'duration': '3 hours',
      'totalMarks': 100,
      'date': '2024-02-15',
      'status': 'Published',
      'questions': 25,
      'createdBy': 'Dr. Sharma',
      'difficulty': 'Medium',
    },
    {
      'id': 'EP002',
      'title': 'English Literature Final Exam',
      'class': 'Class 12',
      'subject': 'English',
      'examType': 'Final',
      'duration': '3 hours',
      'totalMarks': 100,
      'date': '2024-03-20',
      'status': 'Draft',
      'questions': 30,
      'createdBy': 'Ms. Priya',
      'difficulty': 'Hard',
    },
    {
      'id': 'EP003',
      'title': 'Science Unit Test',
      'class': 'Class 8',
      'subject': 'Science',
      'examType': 'Unit Test',
      'duration': '2 hours',
      'totalMarks': 50,
      'date': '2024-02-10',
      'status': 'Published',
      'questions': 20,
      'createdBy': 'Mr. Kumar',
      'difficulty': 'Easy',
    },
    {
      'id': 'EP004',
      'title': 'History Annual Exam',
      'class': 'Class 9',
      'subject': 'History',
      'examType': 'Annual',
      'duration': '3 hours',
      'totalMarks': 80,
      'date': '2024-04-15',
      'status': 'Scheduled',
      'questions': 35,
      'createdBy': 'Dr. Singh',
      'difficulty': 'Medium',
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
          'Exam Papers Management',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF2196F3),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Papers', icon: Icon(Icons.description)),
            Tab(text: 'Create', icon: Icon(Icons.add_circle)),
            Tab(text: 'Templates', icon: Icon(Icons.library_books)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics)),
          ],
        ),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF2196F3), Color(0xFF1976D2)],
          ),
        ),
        child: TabBarView(
          controller: _tabController,
          children: [
            _buildPapersTab(),
            _buildCreateTab(),
            _buildTemplatesTab(),
            _buildReportsTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildPapersTab() {
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
                  '📝 Exam Papers Manager',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2196F3),
                  ),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Manage and organize exam question papers! 📚✨',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildStatCard('Total Papers', '${_examPapers.length}',
                        Icons.description, const Color(0xFF4CAF50)),
                    _buildStatCard(
                        'Published',
                        '${_examPapers.where((p) => p['status'] == 'Published').length}',
                        Icons.publish,
                        const Color(0xFF2196F3)),
                    _buildStatCard(
                        'Draft',
                        '${_examPapers.where((p) => p['status'] == 'Draft').length}',
                        Icons.edit,
                        const Color(0xFFFF9800)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Filters
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
            child: Row(
              children: [
                Expanded(
                    child: _buildFilterDropdown(
                        'Class',
                        _selectedClass,
                        _getClasses(),
                        (value) => setState(() => _selectedClass = value!))),
                const SizedBox(width: 10),
                Expanded(
                    child: _buildFilterDropdown(
                        'Subject',
                        _selectedSubject,
                        _getSubjects(),
                        (value) => setState(() => _selectedSubject = value!))),
                const SizedBox(width: 10),
                Expanded(
                    child: _buildFilterDropdown(
                        'Type',
                        _selectedExamType,
                        _getExamTypes(),
                        (value) => setState(() => _selectedExamType = value!))),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Papers List
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
                      color: Color(0xFF2196F3),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.description, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Exam Papers',
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
                      itemCount: _getFilteredPapers().length,
                      itemBuilder: (context, index) {
                        final paper = _getFilteredPapers()[index];
                        return _buildPaperItem(paper);
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

  Widget _buildCreateTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: SingleChildScrollView(
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
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '✏️ Create New Exam Paper',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2196F3),
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Design comprehensive exam papers with ease! 📝',
                    style: TextStyle(fontSize: 16, color: Colors.grey),
                  ),
                  const SizedBox(height: 30),

                  // Form fields
                  _buildFormField('Paper Title', 'Enter exam paper title'),
                  const SizedBox(height: 20),

                  Row(
                    children: [
                      Expanded(child: _buildFormField('Class', 'Select class')),
                      const SizedBox(width: 15),
                      Expanded(
                          child: _buildFormField('Subject', 'Select subject')),
                    ],
                  ),
                  const SizedBox(height: 20),

                  Row(
                    children: [
                      Expanded(
                          child: _buildFormField('Exam Type', 'Select type')),
                      const SizedBox(width: 15),
                      Expanded(
                          child: _buildFormField('Duration', 'Enter duration')),
                    ],
                  ),
                  const SizedBox(height: 20),

                  Row(
                    children: [
                      Expanded(
                          child: _buildFormField('Total Marks', 'Enter marks')),
                      const SizedBox(width: 15),
                      Expanded(
                          child: _buildFormField(
                              'Difficulty', 'Select difficulty')),
                    ],
                  ),
                  const SizedBox(height: 30),

                  // Action buttons
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _createPaper,
                          icon: const Icon(Icons.save),
                          label: const Text('Save as Draft'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFFF9800),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 15),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 15),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _publishPaper,
                          icon: const Icon(Icons.publish),
                          label: const Text('Publish Paper'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF4CAF50),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 15),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
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
      ),
    );
  }

  Widget _buildTemplatesTab() {
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
                  '📚 Paper Templates',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2196F3),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Use pre-designed templates for quick paper creation! 🚀',
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
                _buildTemplateCard(
                    'Mathematics Template',
                    'Standard math paper format',
                    Icons.calculate,
                    const Color(0xFF4CAF50)),
                _buildTemplateCard(
                    'Science Template',
                    'Lab and theory questions',
                    Icons.science,
                    const Color(0xFF2196F3)),
                _buildTemplateCard(
                    'Language Template',
                    'Literature and grammar',
                    Icons.language,
                    const Color(0xFFFF9800)),
                _buildTemplateCard(
                    'Social Studies Template',
                    'History and geography',
                    Icons.public,
                    const Color(0xFF9C27B0)),
              ],
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
                  '📊 Exam Paper Reports',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2196F3),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Analyze paper statistics and performance! 📈',
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
                _buildReportCard(
                    'Paper Statistics',
                    'View paper creation stats',
                    Icons.bar_chart,
                    const Color(0xFF4CAF50)),
                _buildReportCard(
                    'Subject Analysis',
                    'Papers by subject breakdown',
                    Icons.pie_chart,
                    const Color(0xFF2196F3)),
                _buildReportCard(
                    'Difficulty Distribution',
                    'Easy vs medium vs hard papers',
                    Icons.trending_up,
                    const Color(0xFFFF9800)),
                _buildReportCard(
                    'Usage Reports',
                    'Most used templates and formats',
                    Icons.assessment,
                    const Color(0xFF9C27B0)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(
      String title, String value, IconData icon, Color color) {
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

  Widget _buildFilterDropdown(String label, String value, List<String> items,
      Function(String?) onChanged) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF2196F3),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          initialValue: value,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          items: items.map((String item) {
            return DropdownMenuItem<String>(
              value: item,
              child: Text(item, style: const TextStyle(fontSize: 14)),
            );
          }).toList(),
          onChanged: onChanged,
        ),
      ],
    );
  }

  Widget _buildPaperItem(Map<String, dynamic> paper) {
    Color statusColor = _getStatusColor(paper['status']);

    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
        border: Border.left(color: statusColor, width: 4),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  paper['title'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2196F3),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  paper['status'],
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              _buildInfoChip('${paper['class']}', Icons.class_),
              const SizedBox(width: 10),
              _buildInfoChip('${paper['subject']}', Icons.book),
              const SizedBox(width: 10),
              _buildInfoChip('${paper['examType']}', Icons.quiz),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(Icons.schedule, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 5),
              Text('${paper['duration']}',
                  style: TextStyle(color: Colors.grey[600])),
              const SizedBox(width: 20),
              Icon(Icons.grade, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 5),
              Text('${paper['totalMarks']} marks',
                  style: TextStyle(color: Colors.grey[600])),
              const SizedBox(width: 20),
              Icon(Icons.help, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 5),
              Text('${paper['questions']} questions',
                  style: TextStyle(color: Colors.grey[600])),
            ],
          ),
          const SizedBox(height: 15),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Created by: ${paper['createdBy']}',
                style: TextStyle(
                  color: Colors.grey[600],
                  fontSize: 12,
                ),
              ),
              Row(
                children: [
                  TextButton.icon(
                    onPressed: () => _editPaper(paper),
                    icon: const Icon(Icons.edit, size: 16),
                    label: const Text('Edit'),
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFFFF9800),
                    ),
                  ),
                  TextButton.icon(
                    onPressed: () => _previewPaper(paper),
                    icon: const Icon(Icons.visibility, size: 16),
                    label: const Text('Preview'),
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFF2196F3),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoChip(String label, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0xFF2196F3).withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: const Color(0xFF2196F3)),
          const SizedBox(width: 4),
          Text(
            label,
            style: const TextStyle(
              color: Color(0xFF2196F3),
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFormField(String label, String hint) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF2196F3),
          ),
        ),
        const SizedBox(height: 5),
        TextFormField(
          decoration: InputDecoration(
            hintText: hint,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
        ),
      ],
    );
  }

  Widget _buildTemplateCard(
      String title, String description, IconData icon, Color color) {
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

  Widget _buildReportCard(
      String title, String description, IconData icon, Color color) {
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

  Color _getStatusColor(String status) {
    switch (status) {
      case 'Published':
        return const Color(0xFF4CAF50);
      case 'Draft':
        return const Color(0xFFFF9800);
      case 'Scheduled':
        return const Color(0xFF2196F3);
      default:
        return Colors.grey;
    }
  }

  List<String> _getClasses() {
    return [
      'All Classes',
      ...{..._examPapers.map((p) => p['class'] as String)}
    ];
  }

  List<String> _getSubjects() {
    return [
      'All Subjects',
      ...{..._examPapers.map((p) => p['subject'] as String)}
    ];
  }

  List<String> _getExamTypes() {
    return [
      'All Types',
      ...{..._examPapers.map((p) => p['examType'] as String)}
    ];
  }

  List<Map<String, dynamic>> _getFilteredPapers() {
    return _examPapers.where((paper) {
      bool classMatch =
          _selectedClass == 'All Classes' || paper['class'] == _selectedClass;
      bool subjectMatch = _selectedSubject == 'All Subjects' ||
          paper['subject'] == _selectedSubject;
      bool typeMatch = _selectedExamType == 'All Types' ||
          paper['examType'] == _selectedExamType;
      return classMatch && subjectMatch && typeMatch;
    }).toList();
  }

  void _createPaper() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Paper saved as draft successfully!')),
    );
  }

  void _publishPaper() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Paper published successfully!')),
    );
  }

  void _editPaper(Map<String, dynamic> paper) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Editing ${paper['title']}...')),
    );
  }

  void _previewPaper(Map<String, dynamic> paper) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Previewing ${paper['title']}...')),
    );
  }
}
