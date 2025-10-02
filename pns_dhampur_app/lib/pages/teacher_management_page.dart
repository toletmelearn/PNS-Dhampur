import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';

class TeacherManagementPage extends StatefulWidget {
  final String token;

  const TeacherManagementPage({Key? key, required this.token}) : super(key: key);

  @override
  State<TeacherManagementPage> createState() => _TeacherManagementPageState();
}

class _TeacherManagementPageState extends State<TeacherManagementPage> with TickerProviderStateMixin {
  late TabController _tabController;
  final _formKey = GlobalKey<FormState>();
  
  // Form Controllers
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _qualificationController = TextEditingController();
  final _subjectController = TextEditingController();
  final _experienceController = TextEditingController();
  final _salaryController = TextEditingController();
  final _joiningDateController = TextEditingController();

  // Document paths
  String? _resumePath;
  String? _qualificationCertPath;
  String? _experienceCertPath;
  String? _photoPath;
  String? _aadharPath;

  // Sample teacher data
  final List<Map<String, dynamic>> _teachers = [
    {
      'id': 1,
      'name': 'Mrs. Priya Sharma',
      'email': 'priya.sharma@pnsdhampur.edu',
      'phone': '+91 9876543210',
      'subject': 'Mathematics',
      'qualification': 'M.Sc Mathematics, B.Ed',
      'experience': '8 years',
      'salary': 35000,
      'joiningDate': '15 Apr 2020',
      'status': 'Active',
      'photo': '👩‍🏫',
    },
    {
      'id': 2,
      'name': 'Mr. Rajesh Kumar',
      'email': 'rajesh.kumar@pnsdhampur.edu',
      'phone': '+91 9876543211',
      'subject': 'Science',
      'qualification': 'M.Sc Physics, B.Ed',
      'experience': '12 years',
      'salary': 40000,
      'joiningDate': '10 Jun 2018',
      'status': 'Active',
      'photo': '👨‍🏫',
    },
    {
      'id': 3,
      'name': 'Ms. Anita Patel',
      'email': 'anita.patel@pnsdhampur.edu',
      'phone': '+91 9876543212',
      'subject': 'English',
      'qualification': 'M.A English, B.Ed',
      'experience': '5 years',
      'salary': 30000,
      'joiningDate': '20 Aug 2021',
      'status': 'Active',
      'photo': '👩‍🏫',
    },
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('👩‍🏫', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Teacher Management',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF9C27B0),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: '👥 Teachers'),
            Tab(text: '➕ Add Teacher'),
            Tab(text: '📊 Statistics'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildTeachersListTab(),
          _buildAddTeacherTab(),
          _buildStatisticsTab(),
        ],
      ),
    );
  }

  Widget _buildTeachersListTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _teachers.length,
      itemBuilder: (context, index) {
        final teacher = _teachers[index];
        return _buildTeacherCard(teacher);
      },
    );
  }

  Widget _buildAddTeacherTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF9C27B0), Color(0xFFBA68C8)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '👩‍🏫 Add New Teacher',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Fill all details carefully! 📝',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Personal Information
            _buildSectionCard(
              title: '👤 Personal Information',
              color: const Color(0xFF2196F3),
              children: [
                _buildTextField(
                  controller: _nameController,
                  label: 'Full Name',
                  icon: Icons.person,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter name' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _emailController,
                  label: 'Email Address',
                  icon: Icons.email,
                  keyboardType: TextInputType.emailAddress,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter email' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _phoneController,
                  label: 'Phone Number',
                  icon: Icons.phone,
                  keyboardType: TextInputType.phone,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter phone' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _addressController,
                  label: 'Address',
                  icon: Icons.home,
                  maxLines: 3,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter address' : null,
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Professional Information
            _buildSectionCard(
              title: '🎓 Professional Information',
              color: const Color(0xFF4CAF50),
              children: [
                _buildTextField(
                  controller: _qualificationController,
                  label: 'Qualification',
                  icon: Icons.school,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter qualification' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _subjectController,
                  label: 'Subject/Department',
                  icon: Icons.book,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter subject' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _experienceController,
                  label: 'Experience (in years)',
                  icon: Icons.work,
                  keyboardType: TextInputType.number,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter experience' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _salaryController,
                  label: 'Monthly Salary',
                  icon: Icons.currency_rupee,
                  keyboardType: TextInputType.number,
                  validator: (value) => value?.isEmpty ?? true ? 'Please enter salary' : null,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  controller: _joiningDateController,
                  label: 'Joining Date',
                  icon: Icons.calendar_today,
                  readOnly: true,
                  onTap: () => _selectDate(context),
                  validator: (value) => value?.isEmpty ?? true ? 'Please select joining date' : null,
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Document Upload
            _buildSectionCard(
              title: '📄 Document Upload',
              color: const Color(0xFFFF5722),
              children: [
                _buildDocumentUpload(
                  title: 'Resume/CV',
                  emoji: '📄',
                  filePath: _resumePath,
                  onTap: () => _pickFile('resume'),
                ),
                const SizedBox(height: 16),
                _buildDocumentUpload(
                  title: 'Qualification Certificate',
                  emoji: '🎓',
                  filePath: _qualificationCertPath,
                  onTap: () => _pickFile('qualification'),
                ),
                const SizedBox(height: 16),
                _buildDocumentUpload(
                  title: 'Experience Certificate',
                  emoji: '💼',
                  filePath: _experienceCertPath,
                  onTap: () => _pickFile('experience'),
                ),
                const SizedBox(height: 16),
                _buildDocumentUpload(
                  title: 'Photo',
                  emoji: '📸',
                  filePath: _photoPath,
                  onTap: () => _pickFile('photo'),
                ),
                const SizedBox(height: 16),
                _buildDocumentUpload(
                  title: 'Aadhar Card',
                  emoji: '🆔',
                  filePath: _aadharPath,
                  onTap: () => _pickFile('aadhar'),
                ),
              ],
            ),
            const SizedBox(height: 30),

            // Submit Button
            Container(
              width: double.infinity,
              height: 56,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF6C63FF), Color(0xFF9C88FF)],
                ),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF6C63FF).withOpacity(0.3),
                    blurRadius: 12,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: ElevatedButton(
                onPressed: _submitForm,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.transparent,
                  shadowColor: Colors.transparent,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: const Text(
                  '✅ Add Teacher',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatisticsTab() {
    final totalTeachers = _teachers.length;
    final avgExperience = _teachers.fold<double>(0, (sum, teacher) {
      final exp = double.tryParse(teacher['experience'].toString().replaceAll(' years', '')) ?? 0;
      return sum + exp;
    }) / totalTeachers;
    final avgSalary = _teachers.fold<double>(0, (sum, teacher) => sum + teacher['salary']) / totalTeachers;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Summary Cards
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  title: 'Total Teachers',
                  value: totalTeachers.toString(),
                  icon: Icons.people,
                  color: const Color(0xFF2196F3),
                  emoji: '👥',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  title: 'Avg Experience',
                  value: '${avgExperience.toStringAsFixed(1)} yrs',
                  icon: Icons.work,
                  color: const Color(0xFF4CAF50),
                  emoji: '💼',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  title: 'Avg Salary',
                  value: '₹${avgSalary.toStringAsFixed(0)}',
                  icon: Icons.currency_rupee,
                  color: const Color(0xFF9C27B0),
                  emoji: '💰',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  title: 'Active Teachers',
                  value: _teachers.where((t) => t['status'] == 'Active').length.toString(),
                  icon: Icons.check_circle,
                  color: const Color(0xFF4CAF50),
                  emoji: '✅',
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Subject Distribution
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📚 Subject Distribution',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ..._teachers.map((teacher) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFF9C27B0).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(teacher['photo'], style: const TextStyle(fontSize: 16)),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                teacher['name'],
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              Text(
                                teacher['subject'],
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF4CAF50),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            teacher['experience'],
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  )).toList(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTeacherCard(Map<String, dynamic> teacher) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFF9C27B0).withOpacity(0.3), width: 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF9C27B0).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(teacher['photo'], style: const TextStyle(fontSize: 24)),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        teacher['name'],
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        teacher['subject'],
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      Text(
                        teacher['email'],
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[500],
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFF4CAF50).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    teacher['status'],
                    style: const TextStyle(
                      color: Color(0xFF4CAF50),
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem('📚', 'Qualification', teacher['qualification']),
                ),
                Expanded(
                  child: _buildInfoItem('💼', 'Experience', teacher['experience']),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem('💰', 'Salary', '₹${teacher['salary']}'),
                ),
                Expanded(
                  child: _buildInfoItem('📅', 'Joined', teacher['joiningDate']),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _showTeacherDetails(teacher),
                    icon: const Icon(Icons.visibility, size: 16),
                    label: const Text('View Details'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2196F3),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _editTeacher(teacher),
                    icon: const Icon(Icons.edit, size: 16),
                    label: const Text('Edit'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF9C27B0),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required Color color,
    required List<Widget> children,
  }) {
    return Card(
      elevation: 8,
      shadowColor: color.withOpacity(0.3),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.2), width: 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 16),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    String? Function(String?)? validator,
    TextInputType? keyboardType,
    int maxLines = 1,
    bool readOnly = false,
    VoidCallback? onTap,
  }) {
    return TextFormField(
      controller: controller,
      validator: validator,
      keyboardType: keyboardType,
      maxLines: maxLines,
      readOnly: readOnly,
      onTap: onTap,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: const Color(0xFF6C63FF)),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.withOpacity(0.3)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.withOpacity(0.3)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF6C63FF), width: 2),
        ),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      ),
    );
  }

  Widget _buildDocumentUpload({
    required String title,
    required String emoji,
    String? filePath,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(
            color: filePath != null ? Colors.green : Colors.grey.withOpacity(0.3),
            width: 2,
          ),
          borderRadius: BorderRadius.circular(12),
          color: filePath != null 
              ? Colors.green.withOpacity(0.1) 
              : Colors.grey.withOpacity(0.05),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: filePath != null 
                    ? Colors.green.withOpacity(0.2) 
                    : Colors.grey.withOpacity(0.2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(emoji, style: const TextStyle(fontSize: 24)),
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
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    filePath != null ? 'File selected ✅' : 'Tap to upload file',
                    style: TextStyle(
                      fontSize: 14,
                      color: filePath != null ? Colors.green : Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              filePath != null ? Icons.check_circle : Icons.upload_file,
              color: filePath != null ? Colors.green : Colors.grey,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
    required String emoji,
  }) {
    return Card(
      elevation: 8,
      shadowColor: color.withOpacity(0.3),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [color, color.withOpacity(0.8)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, color: Colors.white, size: 24),
                Text(emoji, style: const TextStyle(fontSize: 24)),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: TextStyle(
                color: Colors.white.withOpacity(0.9),
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(String emoji, String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(emoji, style: const TextStyle(fontSize: 16)),
            const SizedBox(width: 8),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(1990),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(primary: Color(0xFF6C63FF)),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        _joiningDateController.text = "${picked.day}/${picked.month}/${picked.year}";
      });
    }
  }

  Future<void> _pickFile(String documentType) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    );

    if (result != null) {
      setState(() {
        switch (documentType) {
          case 'resume':
            _resumePath = result.files.single.path;
            break;
          case 'qualification':
            _qualificationCertPath = result.files.single.path;
            break;
          case 'experience':
            _experienceCertPath = result.files.single.path;
            break;
          case 'photo':
            _photoPath = result.files.single.path;
            break;
          case 'aadhar':
            _aadharPath = result.files.single.path;
            break;
        }
      });
    }
  }

  void _submitForm() {
    if (_formKey.currentState!.validate()) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.white),
              SizedBox(width: 8),
              Text('Teacher added successfully! 🎉'),
            ],
          ),
          backgroundColor: Colors.green,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
      _clearForm();
    }
  }

  void _clearForm() {
    _nameController.clear();
    _emailController.clear();
    _phoneController.clear();
    _addressController.clear();
    _qualificationController.clear();
    _subjectController.clear();
    _experienceController.clear();
    _salaryController.clear();
    _joiningDateController.clear();
    setState(() {
      _resumePath = null;
      _qualificationCertPath = null;
      _experienceCertPath = null;
      _photoPath = null;
      _aadharPath = null;
    });
  }

  void _showTeacherDetails(Map<String, dynamic> teacher) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Text(teacher['photo'], style: const TextStyle(fontSize: 24)),
            const SizedBox(width: 8),
            Text(teacher['name']),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('📧 Email: ${teacher['email']}'),
            Text('📞 Phone: ${teacher['phone']}'),
            Text('📚 Subject: ${teacher['subject']}'),
            Text('🎓 Qualification: ${teacher['qualification']}'),
            Text('💼 Experience: ${teacher['experience']}'),
            Text('💰 Salary: ₹${teacher['salary']}'),
            Text('📅 Joined: ${teacher['joiningDate']}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  void _editTeacher(Map<String, dynamic> teacher) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Edit functionality for ${teacher['name']} will be implemented! ✏️'),
        backgroundColor: const Color(0xFF9C27B0),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _qualificationController.dispose();
    _subjectController.dispose();
    _experienceController.dispose();
    _salaryController.dispose();
    _joiningDateController.dispose();
    super.dispose();
  }
}