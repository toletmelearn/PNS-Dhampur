import 'package:flutter/material.dart';

class TeacherExperiencePage extends StatefulWidget {
  final String token;

  const TeacherExperiencePage({Key? key, required this.token}) : super(key: key);

  @override
  _TeacherExperiencePageState createState() => _TeacherExperiencePageState();
}

class _TeacherExperiencePageState extends State<TeacherExperiencePage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Sample data for teachers experience
  final List<Map<String, dynamic>> _teachersExperience = [
    {
      'id': 1,
      'name': 'Mrs. Priya Sharma',
      'subject': 'Mathematics',
      'totalExperience': '12 years',
      'schoolExperience': '5 years',
      'previousSchools': [
        {'name': 'ABC Public School', 'duration': '3 years', 'position': 'Math Teacher'},
        {'name': 'XYZ Academy', 'duration': '4 years', 'position': 'Senior Math Teacher'},
      ],
      'qualifications': ['B.Sc Mathematics', 'B.Ed', 'M.Sc Mathematics'],
      'achievements': ['Best Teacher Award 2022', 'Mathematics Olympiad Coach'],
      'joinDate': '2019-06-15',
      'photo': '👩‍🏫',
    },
    {
      'id': 2,
      'name': 'Mr. Rajesh Kumar',
      'subject': 'Science',
      'totalExperience': '8 years',
      'schoolExperience': '3 years',
      'previousSchools': [
        {'name': 'City High School', 'duration': '5 years', 'position': 'Science Teacher'},
      ],
      'qualifications': ['B.Sc Physics', 'B.Ed', 'M.Sc Physics'],
      'achievements': ['Science Fair Organizer', 'Lab Equipment Specialist'],
      'joinDate': '2021-04-10',
      'photo': '👨‍🏫',
    },
    {
      'id': 3,
      'name': 'Ms. Anita Verma',
      'subject': 'English',
      'totalExperience': '15 years',
      'schoolExperience': '7 years',
      'previousSchools': [
        {'name': 'Modern School', 'duration': '4 years', 'position': 'English Teacher'},
        {'name': 'Elite Academy', 'duration': '4 years', 'position': 'Head of English'},
      ],
      'qualifications': ['B.A English', 'B.Ed', 'M.A English Literature'],
      'achievements': ['Drama Club Coordinator', 'Published Author'],
      'joinDate': '2017-07-01',
      'photo': '👩‍💼',
    },
  ];

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
          'Teachers Experience',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: const Color(0xFF795548),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Overview', icon: Icon(Icons.dashboard)),
            Tab(text: 'Details', icon: Icon(Icons.person_search)),
            Tab(text: 'Reports', icon: Icon(Icons.analytics)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildOverviewTab(),
          _buildDetailsTab(),
          _buildReportsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddExperienceDialog(),
        backgroundColor: const Color(0xFF795548),
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
          // Header Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF795548), Color(0xFF8D6E63)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  '📋 Experience Overview',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Track teacher experience and qualifications! 🎓✨',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 16,
                  ),
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
                  'Total Teachers',
                  '${_teachersExperience.length}',
                  Icons.people,
                  const Color(0xFF4CAF50),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard(
                  'Avg Experience',
                  '11.7 years',
                  Icons.timeline,
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
                  'Senior Teachers',
                  '2',
                  Icons.star,
                  const Color(0xFFFF9800),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatCard(
                  'New Joiners',
                  '1',
                  Icons.new_releases,
                  const Color(0xFF9C27B0),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Teachers List
          const Text(
            'Teachers Experience Summary',
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
            itemCount: _teachersExperience.length,
            itemBuilder: (context, index) {
              final teacher = _teachersExperience[index];
              return _buildTeacherCard(teacher);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildDetailsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Detailed Experience Records',
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
            itemCount: _teachersExperience.length,
            itemBuilder: (context, index) {
              final teacher = _teachersExperience[index];
              return _buildDetailedTeacherCard(teacher);
            },
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
            'Experience Reports',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 16),
          _buildReportCard(
            '📊 Experience Distribution',
            'View experience levels across subjects',
            Icons.bar_chart,
            const Color(0xFF4CAF50),
            () => _generateReport('Experience Distribution'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '🎓 Qualification Summary',
            'Teacher qualifications and certifications',
            Icons.school,
            const Color(0xFF2196F3),
            () => _generateReport('Qualification Summary'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '🏆 Achievement Records',
            'Awards and recognitions received',
            Icons.emoji_events,
            const Color(0xFFFF9800),
            () => _generateReport('Achievement Records'),
          ),
          const SizedBox(height: 12),
          _buildReportCard(
            '📅 Service History',
            'Complete service record analysis',
            Icons.history,
            const Color(0xFF9C27B0),
            () => _generateReport('Service History'),
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

  Widget _buildTeacherCard(Map<String, dynamic> teacher) {
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
              color: const Color(0xFF795548).withOpacity(0.1),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Center(
              child: Text(
                teacher['photo'],
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
                  teacher['name'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2C3E50),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  teacher['subject'],
                  style: const TextStyle(
                    fontSize: 14,
                    color: Color(0xFF7F8C8D),
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(
                      Icons.work_history,
                      size: 16,
                      color: const Color(0xFF795548),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      teacher['totalExperience'],
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF795548),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Icon(
            Icons.arrow_forward_ios,
            size: 16,
            color: const Color(0xFF7F8C8D),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailedTeacherCard(Map<String, dynamic> teacher) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
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
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: const Color(0xFF795548).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(25),
                ),
                child: Center(
                  child: Text(
                    teacher['photo'],
                    style: const TextStyle(fontSize: 20),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      teacher['name'],
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF2C3E50),
                      ),
                    ),
                    Text(
                      teacher['subject'],
                      style: const TextStyle(
                        fontSize: 14,
                        color: Color(0xFF7F8C8D),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          
          // Experience Details
          _buildDetailRow('Total Experience', teacher['totalExperience']),
          _buildDetailRow('School Experience', teacher['schoolExperience']),
          _buildDetailRow('Join Date', teacher['joinDate']),
          
          const SizedBox(height: 12),
          const Text(
            'Qualifications:',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 4),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            children: (teacher['qualifications'] as List<String>).map((qual) {
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF795548).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  qual,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF795548),
                  ),
                ),
              );
            }).toList(),
          ),
          
          const SizedBox(height: 12),
          const Text(
            'Achievements:',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF2C3E50),
            ),
          ),
          const SizedBox(height: 4),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: (teacher['achievements'] as List<String>).map((achievement) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 2),
                child: Row(
                  children: [
                    const Icon(Icons.star, size: 12, color: Color(0xFFFF9800)),
                    const SizedBox(width: 4),
                    Text(
                      achievement,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF7F8C8D),
                      ),
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          SizedBox(
            width: 120,
            child: Text(
              '$label:',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: Color(0xFF7F8C8D),
              ),
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF2C3E50),
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

  void _showAddExperienceDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Add Teacher Experience'),
          content: const Text('This feature will allow adding new teacher experience records.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Feature coming soon!')),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF795548),
              ),
              child: const Text('Add', style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _generateReport(String reportType) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating $reportType report...')),
    );
  }
}