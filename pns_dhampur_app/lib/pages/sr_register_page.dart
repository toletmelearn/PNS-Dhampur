import 'package:flutter/material.dart';

class SRRegisterPage extends StatefulWidget {
  const SRRegisterPage({super.key});

  @override
  State<SRRegisterPage> createState() => _SRRegisterPageState();
}

class _SRRegisterPageState extends State<SRRegisterPage>
    with TickerProviderStateMixin {
  late TabController _tabController;
  String _selectedClass = 'All Classes';
  String _selectedSection = 'All Sections';
  String _selectedStatus = 'All Status';

  // Sample data for SR Register
  final List<Map<String, dynamic>> _srRecords = [
    {
      'id': 'SR001',
      'studentName': 'Aarav Sharma',
      'class': 'Class 10',
      'section': 'A',
      'rollNumber': '101',
      'fatherName': 'Rajesh Sharma',
      'motherName': 'Priya Sharma',
      'dateOfBirth': '2008-05-15',
      'address': '123 Main Street, Delhi',
      'phone': '+91 9876543210',
      'admissionDate': '2023-04-01',
      'status': 'Active',
      'bloodGroup': 'B+',
      'category': 'General',
      'religion': 'Hindu',
      'nationality': 'Indian',
      'previousSchool': 'ABC Public School',
      'tcNumber': 'TC2023001',
      'documents': ['Birth Certificate', 'Aadhar Card', 'Photos'],
    },
    {
      'id': 'SR002',
      'studentName': 'Diya Patel',
      'class': 'Class 9',
      'section': 'B',
      'rollNumber': '205',
      'fatherName': 'Amit Patel',
      'motherName': 'Sunita Patel',
      'dateOfBirth': '2009-08-22',
      'address': '456 Park Avenue, Mumbai',
      'phone': '+91 9876543211',
      'admissionDate': '2023-04-01',
      'status': 'Active',
      'bloodGroup': 'A+',
      'category': 'OBC',
      'religion': 'Hindu',
      'nationality': 'Indian',
      'previousSchool': 'XYZ School',
      'tcNumber': 'TC2023002',
      'documents': ['Birth Certificate', 'Caste Certificate', 'Photos'],
    },
    {
      'id': 'SR003',
      'studentName': 'Arjun Singh',
      'class': 'Class 8',
      'section': 'A',
      'rollNumber': '301',
      'fatherName': 'Vikram Singh',
      'motherName': 'Kavita Singh',
      'dateOfBirth': '2010-12-10',
      'address': '789 Garden Road, Jaipur',
      'phone': '+91 9876543212',
      'admissionDate': '2023-04-01',
      'status': 'Transferred',
      'bloodGroup': 'O+',
      'category': 'General',
      'religion': 'Hindu',
      'nationality': 'Indian',
      'previousSchool': 'DEF Academy',
      'tcNumber': 'TC2023003',
      'documents': ['Birth Certificate', 'Transfer Certificate', 'Photos'],
    },
  ];

  final List<Map<String, dynamic>> _statistics = [
    {
      'title': 'Total Records',
      'value': '1,245',
      'icon': Icons.folder,
      'color': Colors.blue
    },
    {
      'title': 'Active Students',
      'value': '1,180',
      'icon': Icons.person,
      'color': Colors.green
    },
    {
      'title': 'Transferred',
      'value': '45',
      'icon': Icons.transfer_within_a_station,
      'color': Colors.orange
    },
    {
      'title': 'Graduated',
      'value': '20',
      'icon': Icons.school,
      'color': Colors.purple
    },
  ];

  final List<Map<String, dynamic>> _recentActivities = [
    {
      'action': 'New Admission',
      'student': 'Rahul Kumar',
      'class': 'Class 6',
      'time': '2 hours ago',
      'icon': Icons.person_add,
      'color': Colors.green,
    },
    {
      'action': 'Record Updated',
      'student': 'Sneha Gupta',
      'class': 'Class 7',
      'time': '4 hours ago',
      'icon': Icons.edit,
      'color': Colors.blue,
    },
    {
      'action': 'Transfer Certificate',
      'student': 'Mohit Verma',
      'class': 'Class 9',
      'time': '1 day ago',
      'icon': Icons.description,
      'color': Colors.orange,
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
          'SR Register',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFF795548),
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Records'),
            Tab(text: 'Add New'),
            Tab(text: 'Reports'),
            Tab(text: 'Archive'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildRecordsTab(),
          _buildAddNewTab(),
          _buildReportsTab(),
          _buildArchiveTab(),
        ],
      ),
    );
  }

  Widget _buildRecordsTab() {
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
                  initialValue: _selectedClass,
                  decoration: const InputDecoration(
                    labelText: 'Class',
                    border: OutlineInputBorder(),
                    contentPadding:
                        EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: [
                    'All Classes',
                    'Class 6',
                    'Class 7',
                    'Class 8',
                    'Class 9',
                    'Class 10'
                  ].map((String value) {
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
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedSection,
                  decoration: const InputDecoration(
                    labelText: 'Section',
                    border: OutlineInputBorder(),
                    contentPadding:
                        EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Sections', 'A', 'B', 'C'].map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedSection = newValue!;
                    });
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedStatus,
                  decoration: const InputDecoration(
                    labelText: 'Status',
                    border: OutlineInputBorder(),
                    contentPadding:
                        EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: ['All Status', 'Active', 'Transferred', 'Graduated']
                      .map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedStatus = newValue!;
                    });
                  },
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // SR Records List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _srRecords.length,
            itemBuilder: (context, index) {
              final record = _srRecords[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                elevation: 2,
                child: ExpansionTile(
                  leading: CircleAvatar(
                    backgroundColor: _getStatusColor(record['status']),
                    child: Text(
                      record['studentName'][0],
                      style: const TextStyle(
                          color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                  ),
                  title: Text(
                    record['studentName'],
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text(
                      '${record['class']} - ${record['section']} | Roll: ${record['rollNumber']}'),
                  trailing: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _getStatusColor(record['status']).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      record['status'],
                      style: TextStyle(
                        color: _getStatusColor(record['status']),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildDetailRow('SR ID', record['id']),
                          _buildDetailRow(
                              'Father\'s Name', record['fatherName']),
                          _buildDetailRow(
                              'Mother\'s Name', record['motherName']),
                          _buildDetailRow(
                              'Date of Birth', record['dateOfBirth']),
                          _buildDetailRow('Address', record['address']),
                          _buildDetailRow('Phone', record['phone']),
                          _buildDetailRow('Blood Group', record['bloodGroup']),
                          _buildDetailRow('Category', record['category']),
                          _buildDetailRow(
                              'Admission Date', record['admissionDate']),
                          _buildDetailRow('TC Number', record['tcNumber']),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _editRecord(record),
                                  icon: const Icon(Icons.edit, size: 16),
                                  label: const Text('Edit'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.blue,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _generateTC(record),
                                  icon: const Icon(Icons.description, size: 16),
                                  label: const Text('TC'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.green,
                                    foregroundColor: Colors.white,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _printRecord(record),
                                  icon: const Icon(Icons.print, size: 16),
                                  label: const Text('Print'),
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
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildAddNewTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '📝 Add New Student Record',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Personal Information Section
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Personal Information',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    decoration: const InputDecoration(
                      labelText: 'Student Name *',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Father\'s Name *',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Mother\'s Name *',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Date of Birth *',
                            border: OutlineInputBorder(),
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          decoration: const InputDecoration(
                            labelText: 'Blood Group',
                            border: OutlineInputBorder(),
                          ),
                          items: [
                            'A+',
                            'A-',
                            'B+',
                            'B-',
                            'AB+',
                            'AB-',
                            'O+',
                            'O-'
                          ].map((String value) {
                            return DropdownMenuItem<String>(
                              value: value,
                              child: Text(value),
                            );
                          }).toList(),
                          onChanged: (String? newValue) {},
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Academic Information Section
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Academic Information',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          decoration: const InputDecoration(
                            labelText: 'Class *',
                            border: OutlineInputBorder(),
                          ),
                          items: [
                            'Class 6',
                            'Class 7',
                            'Class 8',
                            'Class 9',
                            'Class 10'
                          ].map((String value) {
                            return DropdownMenuItem<String>(
                              value: value,
                              child: Text(value),
                            );
                          }).toList(),
                          onChanged: (String? newValue) {},
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          decoration: const InputDecoration(
                            labelText: 'Section *',
                            border: OutlineInputBorder(),
                          ),
                          items: ['A', 'B', 'C'].map((String value) {
                            return DropdownMenuItem<String>(
                              value: value,
                              child: Text(value),
                            );
                          }).toList(),
                          onChanged: (String? newValue) {},
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Roll Number *',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Admission Date *',
                            border: OutlineInputBorder(),
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Previous School',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Contact Information Section
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Contact Information',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    decoration: const InputDecoration(
                      labelText: 'Address *',
                      border: OutlineInputBorder(),
                    ),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Phone Number *',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          decoration: const InputDecoration(
                            labelText: 'Email (Optional)',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 24),

          // Submit Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => _saveNewRecord(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF795548),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: const Text(
                'Save Student Record',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
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
            '📊 SR Register Reports',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Recent Activities
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Recent Activities',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  ..._recentActivities.map((activity) {
                    return ListTile(
                      leading: CircleAvatar(
                        backgroundColor: activity['color'],
                        child: Icon(activity['icon'],
                            color: Colors.white, size: 20),
                      ),
                      title: Text(activity['action']),
                      subtitle:
                          Text('${activity['student']} - ${activity['class']}'),
                      trailing: Text(
                        activity['time'],
                        style:
                            const TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    );
                  }).toList(),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Report Cards
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildReportCard(
                'Class-wise Report',
                'Students by class distribution',
                Icons.class_,
                Colors.blue,
                () => _generateClassReport(),
              ),
              _buildReportCard(
                'Status Report',
                'Active, transferred, graduated',
                Icons.assessment,
                Colors.green,
                () => _generateStatusReport(),
              ),
              _buildReportCard(
                'Admission Report',
                'Monthly admission trends',
                Icons.trending_up,
                Colors.orange,
                () => _generateAdmissionReport(),
              ),
              _buildReportCard(
                'TC Report',
                'Transfer certificates issued',
                Icons.description,
                Colors.purple,
                () => _generateTCReport(),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildArchiveTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            '🗄️ Archive Management',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),

          // Archive Statistics
          Row(
            children: [
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Icon(Icons.archive, color: Colors.grey[600], size: 32),
                        const SizedBox(height: 8),
                        const Text(
                          '245',
                          style: TextStyle(
                              fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        const Text('Archived Records'),
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
                        Icon(Icons.delete, color: Colors.red[400], size: 32),
                        const SizedBox(height: 8),
                        const Text(
                          '12',
                          style: TextStyle(
                              fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        const Text('Deleted Records'),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Archive Actions
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Archive Actions',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  ListTile(
                    leading: const Icon(Icons.archive, color: Colors.blue),
                    title: const Text('Archive Old Records'),
                    subtitle: const Text(
                        'Move records older than 5 years to archive'),
                    trailing: ElevatedButton(
                      onPressed: () => _archiveOldRecords(),
                      child: const Text('Archive'),
                    ),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.restore, color: Colors.green),
                    title: const Text('Restore Records'),
                    subtitle:
                        const Text('Restore archived records back to active'),
                    trailing: ElevatedButton(
                      onPressed: () => _restoreRecords(),
                      child: const Text('Restore'),
                    ),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.backup, color: Colors.orange),
                    title: const Text('Backup Data'),
                    subtitle:
                        const Text('Create backup of all SR register data'),
                    trailing: ElevatedButton(
                      onPressed: () => _backupData(),
                      child: const Text('Backup'),
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

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              '$label:',
              style: const TextStyle(
                  fontWeight: FontWeight.w500, color: Colors.grey),
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

  Widget _buildReportCard(String title, String subtitle, IconData icon,
      Color color, VoidCallback onTap) {
    return Card(
      elevation: 2,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: color, size: 32),
              const SizedBox(height: 12),
              Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 12, color: Colors.grey),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active':
        return Colors.green;
      case 'transferred':
        return Colors.orange;
      case 'graduated':
        return Colors.purple;
      default:
        return Colors.grey;
    }
  }

  void _editRecord(Map<String, dynamic> record) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Editing record for ${record['studentName']}')),
    );
  }

  void _generateTC(Map<String, dynamic> record) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generating TC for ${record['studentName']}')),
    );
  }

  void _printRecord(Map<String, dynamic> record) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Printing record for ${record['studentName']}')),
    );
  }

  void _saveNewRecord() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('New student record saved successfully!')),
    );
  }

  void _generateClassReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating class-wise report...')),
    );
  }

  void _generateStatusReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating status report...')),
    );
  }

  void _generateAdmissionReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating admission report...')),
    );
  }

  void _generateTCReport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating TC report...')),
    );
  }

  void _archiveOldRecords() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Archiving old records...')),
    );
  }

  void _restoreRecords() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Restoring archived records...')),
    );
  }

  void _backupData() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Creating data backup...')),
    );
  }
}
