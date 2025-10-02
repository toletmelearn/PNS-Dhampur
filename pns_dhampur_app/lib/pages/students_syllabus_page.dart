import 'package:flutter/material.dart';

class StudentsSyllabusPage extends StatefulWidget {
  final String token;
  const StudentsSyllabusPage({Key? key, required this.token}) : super(key: key);

  @override
  _StudentsSyllabusPageState createState() => _StudentsSyllabusPageState();
}

class _StudentsSyllabusPageState extends State<StudentsSyllabusPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'Class 10';
  String _selectedSubject = 'All Subjects';

  // Sample data for syllabus
  final List<Map<String, dynamic>> _syllabusData = [
    {
      'id': 'SYL001',
      'class': 'Class 10',
      'subject': 'Mathematics',
      'chapter': 'Real Numbers',
      'topics': ['Euclid\'s Division Lemma', 'Fundamental Theorem of Arithmetic', 'Rational and Irrational Numbers'],
      'status': 'Completed',
      'progress': 100,
      'startDate': '2024-01-01',
      'endDate': '2024-01-15',
      'assignments': 3,
      'tests': 2,
      'resources': ['NCERT Textbook', 'Reference Book', 'Online Videos'],
    },
    {
      'id': 'SYL002',
      'class': 'Class 10',
      'subject': 'Mathematics',
      'chapter': 'Polynomials',
      'topics': ['Degree of Polynomial', 'Zeros of Polynomial', 'Relationship between Zeros and Coefficients'],
      'status': 'In Progress',
      'progress': 65,
      'startDate': '2024-01-16',
      'endDate': '2024-01-30',
      'assignments': 2,
      'tests': 1,
      'resources': ['NCERT Textbook', 'Practice Worksheets'],
    },
    {
      'id': 'SYL003',
      'class': 'Class 10',
      'subject': 'Science',
      'chapter': 'Light - Reflection and Refraction',
      'topics': ['Laws of Reflection', 'Spherical Mirrors', 'Refraction of Light', 'Lenses'],
      'status': 'Completed',
      'progress': 100,
      'startDate': '2024-01-01',
      'endDate': '2024-01-20',
      'assignments': 4,
      'tests': 2,
      'resources': ['NCERT Textbook', 'Lab Manual', 'Simulation Software'],
    },
    {
      'id': 'SYL004',
      'class': 'Class 10',
      'subject': 'English',
      'chapter': 'First Flight - A Letter to God',
      'topics': ['Story Analysis', 'Character Study', 'Theme Discussion', 'Vocabulary Building'],
      'status': 'In Progress',
      'progress': 40,
      'startDate': '2024-01-10',
      'endDate': '2024-01-25',
      'assignments': 2,
      'tests': 1,
      'resources': ['NCERT Textbook', 'Audio Stories', 'Supplementary Reading'],
    },
    {
      'id': 'SYL005',
      'class': 'Class 10',
      'subject': 'Social Studies',
      'chapter': 'The Rise of Nationalism in Europe',
      'topics': ['French Revolution', 'Nationalism in Europe', 'Making of Germany and Italy'],
      'status': 'Pending',
      'progress': 0,
      'startDate': '2024-02-01',
      'endDate': '2024-02-15',
      'assignments': 3,
      'tests': 1,
      'resources': ['NCERT Textbook', 'Historical Maps', 'Documentary Videos'],
    },
  ];

  // Sample daily work data
  final List<Map<String, dynamic>> _dailyWork = [
    {
      'date': '2024-01-15',
      'class': 'Class 10',
      'subject': 'Mathematics',
      'topic': 'Quadratic Equations - Solving by Factorization',
      'homework': 'Exercise 4.1 - Questions 1 to 10',
      'classwork': 'Solved examples on factorization method',
      'notes': 'Important formulas and shortcuts discussed',
      'teacher': 'Dr. Rajesh Sharma',
    },
    {
      'date': '2024-01-15',
      'class': 'Class 10',
      'subject': 'Science',
      'topic': 'Acids, Bases and Salts - pH Scale',
      'homework': 'Lab report on pH testing of household items',
      'classwork': 'Practical demonstration of pH indicators',
      'notes': 'pH scale ranges and applications',
      'teacher': 'Ms. Priya Singh',
    },
    {
      'date': '2024-01-14',
      'class': 'Class 10',
      'subject': 'English',
      'topic': 'Grammar - Active and Passive Voice',
      'homework': 'Convert 20 sentences from active to passive voice',
      'classwork': 'Rules and examples of voice conversion',
      'notes': 'Special cases in voice change',
      'teacher': 'Mr. Amit Kumar',
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
          'Students Syllabus',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF673AB7),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Syllabus', icon: Icon(Icons.book)),
            Tab(text: 'Daily Work', icon: Icon(Icons.assignment)),
            Tab(text: 'Progress', icon: Icon(Icons.trending_up)),
            Tab(text: 'Resources', icon: Icon(Icons.library_books)),
          ],
        ),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF673AB7), Color(0xFF512DA8)],
          ),
        ),
        child: TabBarView(
          controller: _tabController,
          children: [
            _buildSyllabusTab(),
            _buildDailyWorkTab(),
            _buildProgressTab(),
            _buildResourcesTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildSyllabusTab() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Header with filters
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
                  '📚 Curriculum Overview',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF673AB7),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Complete syllabus tracking and management! 📖✨',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[600],
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(child: _buildClassFilter()),
                    const SizedBox(width: 15),
                    Expanded(child: _buildSubjectFilter()),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
          // Syllabus list
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
                      color: Color(0xFF673AB7),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.list_alt, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Syllabus Chapters',
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
                      itemCount: _getFilteredSyllabus().length,
                      itemBuilder: (context, index) {
                        final syllabus = _getFilteredSyllabus()[index];
                        return _buildSyllabusItem(syllabus);
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

  Widget _buildDailyWorkTab() {
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
                  '📝 Daily Work Tracker',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF673AB7),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Track daily classwork and homework! 📅',
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
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
                      color: Color(0xFF673AB7),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.today, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Recent Daily Work',
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
                      itemCount: _dailyWork.length,
                      itemBuilder: (context, index) {
                        final work = _dailyWork[index];
                        return _buildDailyWorkItem(work);
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

  Widget _buildProgressTab() {
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
            child: Column(
              children: [
                const Text(
                  '📊 Progress Analytics',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF673AB7),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Track syllabus completion progress! 📈',
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
                    _buildProgressCard('Completed', '${_getCompletedCount()}', Icons.check_circle, const Color(0xFF4CAF50)),
                    _buildProgressCard('In Progress', '${_getInProgressCount()}', Icons.schedule, const Color(0xFFFF9800)),
                    _buildProgressCard('Pending', '${_getPendingCount()}', Icons.pending, const Color(0xFFF44336)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          
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
                      color: Color(0xFF673AB7),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.trending_up, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Subject-wise Progress',
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
                      itemCount: _getSubjectProgress().length,
                      itemBuilder: (context, index) {
                        final subject = _getSubjectProgress()[index];
                        return _buildSubjectProgressItem(subject);
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

  Widget _buildResourcesTab() {
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
                  '📚 Learning Resources',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF673AB7),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Access study materials and resources! 🎓',
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
                _buildResourceCard('NCERT Books', 'Official textbooks', Icons.menu_book, const Color(0xFF4CAF50)),
                _buildResourceCard('Reference Books', 'Additional study material', Icons.library_books, const Color(0xFF2196F3)),
                _buildResourceCard('Online Videos', 'Educational content', Icons.play_circle, const Color(0xFFFF9800)),
                _buildResourceCard('Practice Papers', 'Sample questions', Icons.assignment, const Color(0xFF9C27B0)),
                _buildResourceCard('Lab Manuals', 'Practical guides', Icons.science, const Color(0xFFF44336)),
                _buildResourceCard('Study Notes', 'Quick revision', Icons.note, const Color(0xFF607D8B)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildClassFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Select Class',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF673AB7),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          value: _selectedClass,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          items: _getClasses().map((String cls) {
            return DropdownMenuItem<String>(
              value: cls,
              child: Text(cls, style: const TextStyle(fontSize: 14)),
            );
          }).toList(),
          onChanged: (value) => setState(() => _selectedClass = value!),
        ),
      ],
    );
  }

  Widget _buildSubjectFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Select Subject',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF673AB7),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          value: _selectedSubject,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          items: _getSubjects().map((String subject) {
            return DropdownMenuItem<String>(
              value: subject,
              child: Text(subject, style: const TextStyle(fontSize: 14)),
            );
          }).toList(),
          onChanged: (value) => setState(() => _selectedSubject = value!),
        ),
      ],
    );
  }

  Widget _buildSyllabusItem(Map<String, dynamic> syllabus) {
    Color statusColor = _getStatusColor(syllabus['status']);
    
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
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      syllabus['chapter'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF673AB7),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      syllabus['subject'],
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  syllabus['status'],
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
          
          // Progress bar
          Row(
            children: [
              Expanded(
                child: LinearProgressIndicator(
                  value: syllabus['progress'] / 100.0,
                  backgroundColor: Colors.grey[300],
                  valueColor: AlwaysStoppedAnimation<Color>(statusColor),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '${syllabus['progress']}%',
                style: TextStyle(
                  color: statusColor,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          
          // Topics
          Wrap(
            spacing: 5,
            runSpacing: 5,
            children: (syllabus['topics'] as List<String>).map((topic) {
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF673AB7).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  topic,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF673AB7),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 10),
          
          // Stats
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildStatChip('📝 ${syllabus['assignments']} Assignments'),
              _buildStatChip('📊 ${syllabus['tests']} Tests'),
              _buildStatChip('📚 ${(syllabus['resources'] as List).length} Resources'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDailyWorkItem(Map<String, dynamic> work) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
        border: Border.left(color: const Color(0xFF673AB7), width: 4),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      work['topic'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF673AB7),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      '${work['subject']} • ${work['teacher']}',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF673AB7).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  work['date'],
                  style: const TextStyle(
                    color: Color(0xFF673AB7),
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 15),
          
          // Classwork
          _buildWorkSection('📖 Classwork', work['classwork']),
          const SizedBox(height: 10),
          
          // Homework
          _buildWorkSection('📝 Homework', work['homework']),
          const SizedBox(height: 10),
          
          // Notes
          _buildWorkSection('📋 Notes', work['notes']),
        ],
      ),
    );
  }

  Widget _buildWorkSection(String title, String content) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Color(0xFF673AB7),
          ),
        ),
        const SizedBox(height: 5),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[300]!),
          ),
          child: Text(
            content,
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey[700],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildProgressCard(String title, String value, IconData icon, Color color) {
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

  Widget _buildSubjectProgressItem(Map<String, dynamic> subject) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  subject['name'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF673AB7),
                  ),
                ),
              ),
              Text(
                '${subject['progress']}%',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: subject['progress'] >= 75 ? const Color(0xFF4CAF50) : 
                         subject['progress'] >= 50 ? const Color(0xFFFF9800) : const Color(0xFFF44336),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          LinearProgressIndicator(
            value: subject['progress'] / 100.0,
            backgroundColor: Colors.grey[300],
            valueColor: AlwaysStoppedAnimation<Color>(
              subject['progress'] >= 75 ? const Color(0xFF4CAF50) : 
              subject['progress'] >= 50 ? const Color(0xFFFF9800) : const Color(0xFFF44336),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            '${subject['completed']} of ${subject['total']} chapters completed',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildResourceCard(String title, String description, IconData icon, Color color) {
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

  Widget _buildStatChip(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Text(
        text,
        style: const TextStyle(
          fontSize: 11,
          color: Color(0xFF673AB7),
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'Completed':
        return const Color(0xFF4CAF50);
      case 'In Progress':
        return const Color(0xFFFF9800);
      case 'Pending':
        return const Color(0xFFF44336);
      default:
        return Colors.grey;
    }
  }

  List<String> _getClasses() {
    return ['Class 10', 'Class 9', 'Class 8', 'Class 7', 'Class 6'];
  }

  List<String> _getSubjects() {
    return ['All Subjects', ...{..._syllabusData.map((s) => s['subject'] as String)}];
  }

  List<Map<String, dynamic>> _getFilteredSyllabus() {
    return _syllabusData.where((syllabus) {
      bool classMatch = syllabus['class'] == _selectedClass;
      bool subjectMatch = _selectedSubject == 'All Subjects' || syllabus['subject'] == _selectedSubject;
      return classMatch && subjectMatch;
    }).toList();
  }

  int _getCompletedCount() {
    return _syllabusData.where((s) => s['status'] == 'Completed').length;
  }

  int _getInProgressCount() {
    return _syllabusData.where((s) => s['status'] == 'In Progress').length;
  }

  int _getPendingCount() {
    return _syllabusData.where((s) => s['status'] == 'Pending').length;
  }

  List<Map<String, dynamic>> _getSubjectProgress() {
    Map<String, Map<String, int>> subjectStats = {};
    
    for (var syllabus in _syllabusData) {
      String subject = syllabus['subject'];
      if (!subjectStats.containsKey(subject)) {
        subjectStats[subject] = {'total': 0, 'completed': 0};
      }
      subjectStats[subject]!['total'] = subjectStats[subject]!['total']! + 1;
      if (syllabus['status'] == 'Completed') {
        subjectStats[subject]!['completed'] = subjectStats[subject]!['completed']! + 1;
      }
    }
    
    return subjectStats.entries.map((entry) {
      int total = entry.value['total']!;
      int completed = entry.value['completed']!;
      double progress = total > 0 ? (completed / total) * 100 : 0;
      
      return {
        'name': entry.key,
        'total': total,
        'completed': completed,
        'progress': progress.round(),
      };
    }).toList();
  }
}