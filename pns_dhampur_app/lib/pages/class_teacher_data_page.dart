import 'package:flutter/material.dart';

class ClassTeacherDataPage extends StatefulWidget {
  final String token;
  const ClassTeacherDataPage({Key? key, required this.token}) : super(key: key);

  @override
  _ClassTeacherDataPageState createState() => _ClassTeacherDataPageState();
}

class _ClassTeacherDataPageState extends State<ClassTeacherDataPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedDepartment = 'All Departments';

  // Sample data for class teachers
  final List<Map<String, dynamic>> _classTeachers = [
    {
      'id': 'CT001',
      'teacherName': 'Dr. Rajesh Sharma',
      'teacherId': 'T001',
      'class': 'Class 10-A',
      'section': 'A',
      'grade': '10',
      'subject': 'Mathematics',
      'department': 'Science',
      'students': 45,
      'phone': '+91 9876543210',
      'email': 'rajesh.sharma@pnsdhampur.edu.in',
      'experience': '15 years',
      'qualification': 'M.Sc. Mathematics, B.Ed.',
      'joinDate': '2010-06-15',
      'responsibilities': [
        'Class Management',
        'Parent Communication',
        'Academic Monitoring',
        'Discipline'
      ],
      'achievements': ['Best Teacher Award 2023', 'Excellence in Mathematics'],
      'photo': 'assets/images/teacher1.jpg',
      'status': 'Active',
    },
    {
      'id': 'CT002',
      'teacherName': 'Ms. Priya Singh',
      'teacherId': 'T002',
      'class': 'Class 9-B',
      'section': 'B',
      'grade': '9',
      'subject': 'Science',
      'department': 'Science',
      'students': 42,
      'phone': '+91 9876543211',
      'email': 'priya.singh@pnsdhampur.edu.in',
      'experience': '12 years',
      'qualification': 'M.Sc. Physics, B.Ed.',
      'joinDate': '2012-07-01',
      'responsibilities': [
        'Class Management',
        'Lab Supervision',
        'Science Fair Coordination'
      ],
      'achievements': [
        'Innovation in Teaching Award',
        'Science Olympiad Mentor'
      ],
      'photo': 'assets/images/teacher2.jpg',
      'status': 'Active',
    },
    {
      'id': 'CT003',
      'teacherName': 'Mr. Amit Kumar',
      'teacherId': 'T003',
      'class': 'Class 8-A',
      'section': 'A',
      'grade': '8',
      'subject': 'English',
      'department': 'Languages',
      'students': 38,
      'phone': '+91 9876543212',
      'email': 'amit.kumar@pnsdhampur.edu.in',
      'experience': '10 years',
      'qualification': 'M.A. English, B.Ed.',
      'joinDate': '2014-04-10',
      'responsibilities': [
        'Class Management',
        'Literary Activities',
        'Debate Club'
      ],
      'achievements': ['Literary Excellence Award', 'Drama Competition Winner'],
      'photo': 'assets/images/teacher3.jpg',
      'status': 'Active',
    },
    {
      'id': 'CT004',
      'teacherName': 'Mrs. Sunita Verma',
      'teacherId': 'T004',
      'class': 'Class 7-C',
      'section': 'C',
      'grade': '7',
      'subject': 'Social Studies',
      'department': 'Social Sciences',
      'students': 40,
      'phone': '+91 9876543213',
      'email': 'sunita.verma@pnsdhampur.edu.in',
      'experience': '8 years',
      'qualification': 'M.A. History, B.Ed.',
      'joinDate': '2016-08-20',
      'responsibilities': [
        'Class Management',
        'Cultural Activities',
        'History Club'
      ],
      'achievements': [
        'Cultural Program Coordinator',
        'Heritage Walk Organizer'
      ],
      'photo': 'assets/images/teacher4.jpg',
      'status': 'Active',
    },
    {
      'id': 'CT005',
      'teacherName': 'Mr. Vikash Gupta',
      'teacherId': 'T005',
      'class': 'Class 6-B',
      'section': 'B',
      'grade': '6',
      'subject': 'Hindi',
      'department': 'Languages',
      'students': 35,
      'phone': '+91 9876543214',
      'email': 'vikash.gupta@pnsdhampur.edu.in',
      'experience': '6 years',
      'qualification': 'M.A. Hindi, B.Ed.',
      'joinDate': '2018-06-01',
      'responsibilities': [
        'Class Management',
        'Hindi Literary Society',
        'Poetry Competition'
      ],
      'achievements': ['Hindi Literature Award', 'Poetry Recitation Champion'],
      'photo': 'assets/images/teacher5.jpg',
      'status': 'Active',
    },
  ];

  // Sample class statistics
  final Map<String, dynamic> _classStats = {
    'totalClasses': 15,
    'totalTeachers': 25,
    'assignedClasses': 12,
    'unassignedClasses': 3,
    'averageStudents': 40,
    'totalStudents': 600,
  };

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
          'Class Teacher Data',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF9E9E9E),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Teachers', icon: Icon(Icons.person)),
            Tab(text: 'Classes', icon: Icon(Icons.class_)),
            Tab(text: 'Access', icon: Icon(Icons.security)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics)),
          ],
        ),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF9E9E9E), Color(0xFF757575)],
          ),
        ),
        child: TabBarView(
          controller: _tabController,
          children: [
            _buildTeachersTab(),
            _buildClassesTab(),
            _buildAccessTab(),
            _buildReportsTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildTeachersTab() {
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
                  '👨‍🏫 Class Teachers',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Manage class teacher assignments and access! 🏫✨',
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
                    _buildStatCard(
                        'Total Teachers',
                        '${_classStats['totalTeachers']}',
                        Icons.people,
                        const Color(0xFF4CAF50)),
                    _buildStatCard(
                        'Assigned Classes',
                        '${_classStats['assignedClasses']}',
                        Icons.assignment_ind,
                        const Color(0xFF2196F3)),
                    _buildStatCard(
                        'Total Students',
                        '${_classStats['totalStudents']}',
                        Icons.school,
                        const Color(0xFFFF9800)),
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(child: _buildClassFilter()),
                    const SizedBox(width: 15),
                    Expanded(child: _buildDepartmentFilter()),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Teachers list
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
                      color: Color(0xFF9E9E9E),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(15),
                        topRight: Radius.circular(15),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.list, color: Colors.white),
                        SizedBox(width: 10),
                        Text(
                          'Class Teacher List',
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
                      itemCount: _getFilteredTeachers().length,
                      itemBuilder: (context, index) {
                        final teacher = _getFilteredTeachers()[index];
                        return _buildTeacherCard(teacher);
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

  Widget _buildClassesTab() {
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
                  '🏫 Class Management',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Manage class assignments and teacher access! 📚',
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Expanded(
            child: GridView.builder(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 15,
                mainAxisSpacing: 15,
                childAspectRatio: 1.2,
              ),
              itemCount: _getClassList().length,
              itemBuilder: (context, index) {
                final classData = _getClassList()[index];
                return _buildClassCard(classData);
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAccessTab() {
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
                  '🔐 Teacher Access Control',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Manage teacher permissions and access levels! 🛡️',
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Expanded(
            child: ListView(
              children: [
                _buildAccessCard('Student Records',
                    'View and edit student information', Icons.person, true),
                _buildAccessCard('Attendance Management',
                    'Mark and view attendance', Icons.check_circle, true),
                _buildAccessCard('Grade Management', 'Enter and modify grades',
                    Icons.grade, true),
                _buildAccessCard('Parent Communication',
                    'Send messages to parents', Icons.message, true),
                _buildAccessCard('Homework Assignment',
                    'Assign and track homework', Icons.assignment, true),
                _buildAccessCard('Exam Scheduling',
                    'Schedule class tests and exams', Icons.schedule, false),
                _buildAccessCard('Fee Management',
                    'View fee status (read-only)', Icons.payment, false),
                _buildAccessCard('Administrative Reports',
                    'Generate class reports', Icons.analytics, true),
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
                  '📊 Class Reports',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Generate comprehensive class and teacher reports! 📈',
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
                    'Class Performance',
                    'Academic performance analysis',
                    Icons.trending_up,
                    const Color(0xFF4CAF50)),
                _buildReportCard(
                    'Attendance Report',
                    'Class attendance statistics',
                    Icons.check_circle,
                    const Color(0xFF2196F3)),
                _buildReportCard(
                    'Teacher Workload',
                    'Teaching load distribution',
                    Icons.work,
                    const Color(0xFFFF9800)),
                _buildReportCard(
                    'Student Progress',
                    'Individual student tracking',
                    Icons.person,
                    const Color(0xFF9C27B0)),
                _buildReportCard(
                    'Parent Feedback',
                    'Parent-teacher communication',
                    Icons.feedback,
                    const Color(0xFFF44336)),
                _buildReportCard(
                    'Class Activities',
                    'Extracurricular participation',
                    Icons.sports,
                    const Color(0xFF607D8B)),
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
            color: Color(0xFF9E9E9E),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          initialValue: _selectedClass,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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

  Widget _buildDepartmentFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Select Department',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Color(0xFF9E9E9E),
          ),
        ),
        const SizedBox(height: 5),
        DropdownButtonFormField<String>(
          initialValue: _selectedDepartment,
          decoration: InputDecoration(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          items: _getDepartments().map((String dept) {
            return DropdownMenuItem<String>(
              value: dept,
              child: Text(dept, style: const TextStyle(fontSize: 14)),
            );
          }).toList(),
          onChanged: (value) => setState(() => _selectedDepartment = value!),
        ),
      ],
    );
  }

  Widget _buildTeacherCard(Map<String, dynamic> teacher) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(15),
        border: Border.left(color: const Color(0xFF9E9E9E), width: 4),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 25,
                backgroundColor: const Color(0xFF9E9E9E),
                child: Text(
                  teacher['teacherName']
                      .split(' ')
                      .map((n) => n[0])
                      .take(2)
                      .join(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 15),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      teacher['teacherName'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF9E9E9E),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      '${teacher['class']} • ${teacher['subject']}',
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
                  color: const Color(0xFF4CAF50).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  teacher['status'],
                  style: const TextStyle(
                    color: Color(0xFF4CAF50),
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 15),

          // Contact info
          Row(
            children: [
              Expanded(
                child: Row(
                  children: [
                    const Icon(Icons.phone, size: 16, color: Color(0xFF9E9E9E)),
                    const SizedBox(width: 5),
                    Expanded(
                      child: Text(
                        teacher['phone'],
                        style: const TextStyle(fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Row(
                  children: [
                    const Icon(Icons.people,
                        size: 16, color: Color(0xFF9E9E9E)),
                    const SizedBox(width: 5),
                    Text(
                      '${teacher['students']} Students',
                      style: const TextStyle(fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Responsibilities
          Wrap(
            spacing: 5,
            runSpacing: 5,
            children: (teacher['responsibilities'] as List<String>)
                .take(3)
                .map((resp) {
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF9E9E9E).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  resp,
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 10),

          // Action buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildActionButton('View Details', Icons.visibility, () {}),
              _buildActionButton('Edit Access', Icons.edit, () {}),
              _buildActionButton('Contact', Icons.message, () {}),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildClassCard(Map<String, dynamic> classData) {
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
              color: classData['hasTeacher']
                  ? const Color(0xFF4CAF50).withOpacity(0.1)
                  : const Color(0xFFF44336).withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              classData['hasTeacher'] ? Icons.check_circle : Icons.warning,
              color: classData['hasTeacher']
                  ? const Color(0xFF4CAF50)
                  : const Color(0xFFF44336),
              size: 30,
            ),
          ),
          const SizedBox(height: 15),
          Text(
            classData['name'],
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Color(0xFF9E9E9E),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            classData['hasTeacher']
                ? classData['teacher']
                : 'No Teacher Assigned',
            style: TextStyle(
              fontSize: 12,
              color: classData['hasTeacher']
                  ? Colors.grey[600]
                  : const Color(0xFFF44336),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            '${classData['students']} Students',
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[500],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAccessCard(
      String title, String description, IconData icon, bool hasAccess) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
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
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: hasAccess
                  ? const Color(0xFF4CAF50).withOpacity(0.1)
                  : const Color(0xFFF44336).withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              icon,
              color:
                  hasAccess ? const Color(0xFF4CAF50) : const Color(0xFFF44336),
              size: 24,
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF9E9E9E),
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  description,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: hasAccess,
            onChanged: (value) {
              // Handle access toggle
            },
            activeThumbColor: const Color(0xFF4CAF50),
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
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            description,
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
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
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
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
            style: TextStyle(
              fontSize: 10,
              color: color,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton(
      String text, IconData icon, VoidCallback onPressed) {
    return ElevatedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 16),
      label: Text(text, style: const TextStyle(fontSize: 11)),
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF9E9E9E),
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        minimumSize: const Size(0, 32),
      ),
    );
  }

  List<String> _getClasses() {
    return [
      'All Classes',
      ...{..._classTeachers.map((t) => t['class'] as String)}
    ];
  }

  List<String> _getDepartments() {
    return [
      'All Departments',
      ...{..._classTeachers.map((t) => t['department'] as String)}
    ];
  }

  List<Map<String, dynamic>> _getFilteredTeachers() {
    return _classTeachers.where((teacher) {
      bool classMatch = _selectedClass == 'All Classes' ||
          teacher['class']
              .toString()
              .contains(_selectedClass.replaceAll('All Classes', ''));
      bool deptMatch = _selectedDepartment == 'All Departments' ||
          teacher['department'] == _selectedDepartment;
      return classMatch && deptMatch;
    }).toList();
  }

  List<Map<String, dynamic>> _getClassList() {
    return [
      {
        'name': 'Class 10-A',
        'teacher': 'Dr. Rajesh Sharma',
        'students': 45,
        'hasTeacher': true
      },
      {
        'name': 'Class 9-B',
        'teacher': 'Ms. Priya Singh',
        'students': 42,
        'hasTeacher': true
      },
      {
        'name': 'Class 8-A',
        'teacher': 'Mr. Amit Kumar',
        'students': 38,
        'hasTeacher': true
      },
      {
        'name': 'Class 7-C',
        'teacher': 'Mrs. Sunita Verma',
        'students': 40,
        'hasTeacher': true
      },
      {
        'name': 'Class 6-B',
        'teacher': 'Mr. Vikash Gupta',
        'students': 35,
        'hasTeacher': true
      },
      {
        'name': 'Class 10-B',
        'teacher': '',
        'students': 43,
        'hasTeacher': false
      },
      {'name': 'Class 9-A', 'teacher': '', 'students': 41, 'hasTeacher': false},
      {'name': 'Class 8-C', 'teacher': '', 'students': 39, 'hasTeacher': false},
    ];
  }
}
