import 'package:flutter/material.dart';

class AlumniDataPage extends StatefulWidget {
  const AlumniDataPage({super.key});

  @override
  State<AlumniDataPage> createState() => _AlumniDataPageState();
}

class _AlumniDataPageState extends State<AlumniDataPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String _selectedBatch = 'All Batches';
  String _selectedProfession = 'All Professions';
  String _selectedLocation = 'All Locations';

  // Sample data for Alumni
  final List<Map<String, dynamic>> _alumniData = [
    {
      'id': 'ALM001',
      'name': 'Dr. Rajesh Kumar',
      'batch': '2010',
      'class': 'Class 10',
      'currentProfession': 'Doctor',
      'company': 'AIIMS Delhi',
      'location': 'New Delhi',
      'phone': '+91 9876543210',
      'email': 'rajesh.kumar@email.com',
      'achievements': ['MBBS from AIIMS', 'MD in Cardiology', 'Published 15 research papers'],
      'profileImage': 'assets/images/alumni1.jpg',
      'linkedIn': 'linkedin.com/in/rajeshkumar',
      'joinedDate': '2023-01-15',
      'isActive': true,
      'mentorshipAvailable': true,
      'specialization': 'Cardiology',
    },
    {
      'id': 'ALM002',
      'name': 'Priya Sharma',
      'batch': '2012',
      'class': 'Class 10',
      'currentProfession': 'Software Engineer',
      'company': 'Google India',
      'location': 'Bangalore',
      'phone': '+91 9876543211',
      'email': 'priya.sharma@email.com',
      'achievements': ['B.Tech from IIT Delhi', 'Senior Software Engineer at Google', 'Tech Lead for Android Team'],
      'profileImage': 'assets/images/alumni2.jpg',
      'linkedIn': 'linkedin.com/in/priyasharma',
      'joinedDate': '2023-02-20',
      'isActive': true,
      'mentorshipAvailable': true,
      'specialization': 'Android Development',
    },
    {
      'id': 'ALM003',
      'name': 'Amit Patel',
      'batch': '2008',
      'class': 'Class 10',
      'currentProfession': 'Entrepreneur',
      'company': 'TechStart Solutions',
      'location': 'Mumbai',
      'phone': '+91 9876543212',
      'email': 'amit.patel@email.com',
      'achievements': ['Founded 3 successful startups', 'Featured in Forbes 30 Under 30', 'Angel Investor'],
      'profileImage': 'assets/images/alumni3.jpg',
      'linkedIn': 'linkedin.com/in/amitpatel',
      'joinedDate': '2023-03-10',
      'isActive': true,
      'mentorshipAvailable': false,
      'specialization': 'Business Development',
    },
    {
      'id': 'ALM004',
      'name': 'Sneha Gupta',
      'batch': '2015',
      'class': 'Class 10',
      'currentProfession': 'Teacher',
      'company': 'Delhi Public School',
      'location': 'Delhi',
      'phone': '+91 9876543213',
      'email': 'sneha.gupta@email.com',
      'achievements': ['M.Ed from JNU', 'Best Teacher Award 2022', 'Published educational content'],
      'profileImage': 'assets/images/alumni4.jpg',
      'linkedIn': 'linkedin.com/in/snehagupta',
      'joinedDate': '2023-04-05',
      'isActive': true,
      'mentorshipAvailable': true,
      'specialization': 'Mathematics Education',
    },
  ];

  final List<Map<String, dynamic>> _statistics = [
    {'title': 'Total Alumni', 'value': '2,450', 'icon': Icons.people, 'color': Colors.blue},
    {'title': 'Active Members', 'value': '1,890', 'icon': Icons.person_add, 'color': Colors.green},
    {'title': 'Mentors Available', 'value': '245', 'icon': Icons.school, 'color': Colors.orange},
    {'title': 'Success Stories', 'value': '156', 'icon': Icons.star, 'color': Colors.purple},
  ];

  final List<Map<String, dynamic>> _upcomingEvents = [
    {
      'title': 'Annual Alumni Meet 2024',
      'date': '2024-03-15',
      'time': '10:00 AM',
      'venue': 'School Auditorium',
      'type': 'Reunion',
      'registrations': 245,
    },
    {
      'title': 'Career Guidance Workshop',
      'date': '2024-02-20',
      'time': '2:00 PM',
      'venue': 'Online',
      'type': 'Workshop',
      'registrations': 89,
    },
    {
      'title': 'Networking Session',
      'date': '2024-02-28',
      'time': '6:00 PM',
      'venue': 'Hotel Taj',
      'type': 'Networking',
      'registrations': 156,
    },
  ];

  final List<Map<String, dynamic>> _recentAchievements = [
    {
      'alumniName': 'Dr. Rajesh Kumar',
      'achievement': 'Received National Award for Medical Excellence',
      'date': '2024-01-15',
      'category': 'Medical',
    },
    {
      'alumniName': 'Priya Sharma',
      'achievement': 'Promoted to Principal Engineer at Google',
      'date': '2024-01-10',
      'category': 'Technology',
    },
    {
      'alumniName': 'Amit Patel',
      'achievement': 'Startup valued at $50M in Series B funding',
      'date': '2024-01-05',
      'category': 'Business',
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
          'Alumni Data',
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
            Tab(text: 'Directory'),
            Tab(text: 'Achievements'),
            Tab(text: 'Events'),
            Tab(text: 'Network'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDirectoryTab(),
          _buildAchievementsTab(),
          _buildEventsTab(),
          _buildNetworkTab(),
        ],
      ),
    );
  }

  Widget _buildDirectoryTab() {
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
                          fontSize: 18,
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
                  initialValue: _selectedBatch,
                  decoration: const InputDecoration(
                    labelText: 'Batch',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Batches', '2008', '2010', '2012', '2015', '2018', '2020']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedBatch = newValue!;
                    });
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedProfession,
                  decoration: const InputDecoration(
                    labelText: 'Profession',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Professions', 'Doctor', 'Engineer', 'Teacher', 'Entrepreneur', 'Lawyer']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedProfession = newValue!;
                    });
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedLocation,
                  decoration: const InputDecoration(
                    labelText: 'Location',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Locations', 'Delhi', 'Mumbai', 'Bangalore', 'Chennai', 'Pune']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedLocation = newValue!;
                    });
                  },
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // Alumni Directory List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _alumniData.length,
            itemBuilder: (context, index) {
              final alumni = _alumniData[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                elevation: 2,
                child: ExpansionTile(
                  leading: CircleAvatar(
                    backgroundColor: const Color(0xFF673AB7),
                    child: Text(
                      alumni['name'][0],
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                  ),
                  title: Text(
                    alumni['name'],
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text('${alumni['currentProfession']} at ${alumni['company']} | Batch ${alumni['batch']}'),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (alumni['mentorshipAvailable'])
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.green.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Text(
                            'Mentor',
                            style: TextStyle(
                              color: Colors.green,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      const SizedBox(width: 8),
                      Icon(
                        alumni['isActive'] ? Icons.circle : Icons.circle_outlined,
                        color: alumni['isActive'] ? Colors.green : Colors.grey,
                        size: 12,
                      ),
                    ],
                  ),
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildAlumniDetailRow('Location', alumni['location']),
                          _buildAlumniDetailRow('Email', alumni['email']),
                          _buildAlumniDetailRow('Phone', alumni['phone']),
                          _buildAlumniDetailRow('Specialization', alumni['specialization']),
                          const SizedBox(height: 12),
                          const Text(
                            'Achievements:',
                            style: TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 4),
                          ...alumni['achievements'].map<Widget>((achievement) {
                            return Padding(
                              padding: const EdgeInsets.symmetric(vertical: 2),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('• ', style: TextStyle(fontWeight: FontWeight.bold)),
                                  Expanded(child: Text(achievement)),
                                ],
                              ),
                            );
                          }).toList(),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _contactAlumni(alumni),
                                  icon: const Icon(Icons.message, size: 16),
                                  label: const Text('Contact'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.blue,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              if (alumni['mentorshipAvailable'])
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () => _requestMentorship(alumni),
                                    icon: const Icon(Icons.school, size: 16),
                                    label: const Text('Mentor'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.green,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _viewProfile(alumni),
                                  icon: const Icon(Icons.person, size: 16),
                                  label: const Text('Profile'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.purple,
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

  Widget _buildAchievementsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '🏆 Alumni Achievements',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Recent Achievements
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Recent Achievements',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  ..._recentAchievements.map((achievement) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey[300]!),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                _getCategoryIcon(achievement['category']),
                                color: _getCategoryColor(achievement['category']),
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  achievement['alumniName'],
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ),
                              Text(
                                achievement['date'],
                                style: const TextStyle(fontSize: 12, color: Colors.grey),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(achievement['achievement']),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: _getCategoryColor(achievement['category']).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              achievement['category'],
                              style: TextStyle(
                                color: _getCategoryColor(achievement['category']),
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
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

          // Achievement Categories
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildAchievementCard('Medical Excellence', '45 Awards', Icons.medical_services, Colors.red),
              _buildAchievementCard('Technology Innovation', '78 Patents', Icons.computer, Colors.blue),
              _buildAchievementCard('Business Success', '23 Startups', Icons.business, Colors.green),
              _buildAchievementCard('Academic Research', '156 Papers', Icons.school, Colors.orange),
              _buildAchievementCard('Social Impact', '34 NGOs', Icons.volunteer_activism, Colors.purple),
              _buildAchievementCard('Sports Excellence', '12 Medals', Icons.sports, Colors.teal),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEventsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '📅 Alumni Events',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Upcoming Events
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Upcoming Events',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  ..._upcomingEvents.map((event) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue[50],
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.blue[200]!),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                _getEventIcon(event['type']),
                                color: Colors.blue,
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  event['title'],
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.blue.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  event['type'],
                                  style: const TextStyle(
                                    color: Colors.blue,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                              const SizedBox(width: 4),
                              Text('${event['date']} at ${event['time']}'),
                              const SizedBox(width: 16),
                              Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                              const SizedBox(width: 4),
                              Text(event['venue']),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Icon(Icons.people, size: 16, color: Colors.grey[600]),
                              const SizedBox(width: 4),
                              Text('${event['registrations']} registered'),
                              const Spacer(),
                              ElevatedButton(
                                onPressed: () => _registerForEvent(event),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                ),
                                child: const Text('Register'),
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

          // Event Management
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Event Management',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _createEvent(),
                          icon: const Icon(Icons.add),
                          label: const Text('Create Event'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _viewEventHistory(),
                          icon: const Icon(Icons.history),
                          label: const Text('Event History'),
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
          ),
        ],
      ),
    );
  }

  Widget _buildNetworkTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '🌐 Alumni Network',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Network Statistics
          Row(
            children: [
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Icon(Icons.connect_without_contact, color: Colors.blue, size: 32),
                        const SizedBox(height: 8),
                        const Text(
                          '1,245',
                          style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        const Text('Connections'),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Icon(Icons.groups, color: Colors.green, size: 32),
                        const SizedBox(height: 8),
                        const Text(
                          '45',
                          style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        const Text('Groups'),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Professional Networks
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Professional Networks',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildNetworkItem('Medical Professionals', '245 members', Icons.medical_services, Colors.red),
                  _buildNetworkItem('Tech Entrepreneurs', '189 members', Icons.computer, Colors.blue),
                  _buildNetworkItem('Education Sector', '156 members', Icons.school, Colors.green),
                  _buildNetworkItem('Business Leaders', '134 members', Icons.business, Colors.orange),
                  _buildNetworkItem('Government Services', '98 members', Icons.account_balance, Colors.purple),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Mentorship Program
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Mentorship Program',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.green[50],
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.green[200]!),
                          ),
                          child: Column(
                            children: [
                              Icon(Icons.person_add, color: Colors.green, size: 32),
                              const SizedBox(height: 8),
                              const Text(
                                '245',
                                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                              ),
                              const Text('Available Mentors'),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.blue[50],
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.blue[200]!),
                          ),
                          child: Column(
                            children: [
                              Icon(Icons.school, color: Colors.blue, size: 32),
                              const SizedBox(height: 8),
                              const Text(
                                '189',
                                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                              ),
                              const Text('Active Mentorships'),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => _joinMentorshipProgram(),
                      icon: const Icon(Icons.handshake),
                      label: const Text('Join Mentorship Program'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF673AB7),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
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

  Widget _buildAlumniDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: const TextStyle(fontWeight: FontWeight.w500, color: Colors.grey),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAchievementCard(String title, String count, IconData icon, Color color) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color, size: 32),
            const SizedBox(height: 12),
            Text(
              count,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: const TextStyle(fontSize: 12),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNetworkItem(String title, String members, IconData icon, Color color) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.1),
          child: Icon(icon, color: color),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(members),
        trailing: ElevatedButton(
          onPressed: () => _joinNetwork(title),
          style: ElevatedButton.styleFrom(
            backgroundColor: color,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          ),
          child: const Text('Join'),
        ),
      ),
    );
  }

  IconData _getCategoryIcon(String category) {
    switch (category.toLowerCase()) {
      case 'medical':
        return Icons.medical_services;
      case 'technology':
        return Icons.computer;
      case 'business':
        return Icons.business;
      default:
        return Icons.star;
    }
  }

  Color _getCategoryColor(String category) {
    switch (category.toLowerCase()) {
      case 'medical':
        return Colors.red;
      case 'technology':
        return Colors.blue;
      case 'business':
        return Colors.green;
      default:
        return Colors.orange;
    }
  }

  IconData _getEventIcon(String type) {
    switch (type.toLowerCase()) {
      case 'reunion':
        return Icons.people;
      case 'workshop':
        return Icons.work;
      case 'networking':
        return Icons.connect_without_contact;
      default:
        return Icons.event;
    }
  }

  void _contactAlumni(Map<String, dynamic> alumni) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Contacting ${alumni['name']}...')),
    );
  }

  void _requestMentorship(Map<String, dynamic> alumni) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Mentorship request sent to ${alumni['name']}')),
    );
  }

  void _viewProfile(Map<String, dynamic> alumni) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Viewing profile of ${alumni['name']}')),
    );
  }

  void _registerForEvent(Map<String, dynamic> event) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Registered for ${event['title']}')),
    );
  }

  void _createEvent() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Opening event creation form...')),
    );
  }

  void _viewEventHistory() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Loading event history...')),
    );
  }

  void _joinNetwork(String networkName) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Joining $networkName network...')),
    );
  }

  void _joinMentorshipProgram() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Joining mentorship program...')),
    );
  }
}