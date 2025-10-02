import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';

class StudentManagementPage extends StatefulWidget {
  final String token;

  const StudentManagementPage({Key? key, required this.token}) : super(key: key);

  @override
  State<StudentManagementPage> createState() => _StudentManagementPageState();
}

class _StudentManagementPageState extends State<StudentManagementPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _fatherNameController = TextEditingController();
  final _motherNameController = TextEditingController();
  final _dobController = TextEditingController();
  final _classController = TextEditingController();
  final _rollNumberController = TextEditingController();
  final _addressController = TextEditingController();
  final _phoneController = TextEditingController();

  String? _birthCertificatePath;
  String? _aadharCardPath;
  String? _photoPath;

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
              child: const Text('👦', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Student Management',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFFFF9800),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFF9800), Color(0xFFFFB74D)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📚 Add New Student',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Fill all details carefully! 🎯',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Student Form
            Form(
              key: _formKey,
              child: Column(
                children: [
                  // Personal Information Section
                  _buildSectionCard(
                    title: '👤 Personal Information',
                    color: const Color(0xFF2196F3),
                    children: [
                      _buildTextField(
                        controller: _nameController,
                        label: 'Student Name',
                        icon: Icons.person,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter student name';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(
                        controller: _fatherNameController,
                        label: "Father's Name",
                        icon: Icons.man,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return "Please enter father's name";
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(
                        controller: _motherNameController,
                        label: "Mother's Name",
                        icon: Icons.woman,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return "Please enter mother's name";
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(
                        controller: _dobController,
                        label: 'Date of Birth',
                        icon: Icons.cake,
                        readOnly: true,
                        onTap: () => _selectDate(context),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please select date of birth';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Academic Information Section
                  _buildSectionCard(
                    title: '🎓 Academic Information',
                    color: const Color(0xFF4CAF50),
                    children: [
                      _buildTextField(
                        controller: _classController,
                        label: 'Class',
                        icon: Icons.class_,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter class';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(
                        controller: _rollNumberController,
                        label: 'Roll Number',
                        icon: Icons.numbers,
                        keyboardType: TextInputType.number,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter roll number';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Contact Information Section
                  _buildSectionCard(
                    title: '📞 Contact Information',
                    color: const Color(0xFF9C27B0),
                    children: [
                      _buildTextField(
                        controller: _addressController,
                        label: 'Address',
                        icon: Icons.home,
                        maxLines: 3,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter address';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(
                        controller: _phoneController,
                        label: 'Phone Number',
                        icon: Icons.phone,
                        keyboardType: TextInputType.phone,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter phone number';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Document Upload Section
                  _buildSectionCard(
                    title: '📄 Document Upload',
                    color: const Color(0xFFFF5722),
                    children: [
                      _buildDocumentUpload(
                        title: 'Birth Certificate',
                        emoji: '📜',
                        filePath: _birthCertificatePath,
                        onTap: () => _pickFile('birth_certificate'),
                      ),
                      const SizedBox(height: 16),
                      _buildDocumentUpload(
                        title: 'Aadhar Card',
                        emoji: '🆔',
                        filePath: _aadharCardPath,
                        onTap: () => _pickFile('aadhar_card'),
                      ),
                      const SizedBox(height: 16),
                      _buildDocumentUpload(
                        title: 'Student Photo',
                        emoji: '📸',
                        filePath: _photoPath,
                        onTap: () => _pickFile('photo'),
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
                        '✅ Add Student',
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
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: color.withOpacity(0.2),
            width: 1,
          ),
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
            style: BorderStyle.solid,
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
                    filePath != null 
                        ? 'File selected ✅' 
                        : 'Tap to upload file',
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

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now().subtract(const Duration(days: 365 * 10)),
      firstDate: DateTime(1990),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF6C63FF),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        _dobController.text = "${picked.day}/${picked.month}/${picked.year}";
      });
    }
  }

  Future<void> _pickFile(String documentType) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    );

    if (result != null) {
      setState(() {
        switch (documentType) {
          case 'birth_certificate':
            _birthCertificatePath = result.files.single.path;
            break;
          case 'aadhar_card':
            _aadharCardPath = result.files.single.path;
            break;
          case 'photo':
            _photoPath = result.files.single.path;
            break;
        }
      });
    }
  }

  void _submitForm() {
    if (_formKey.currentState!.validate()) {
      // Check if all documents are uploaded
      if (_birthCertificatePath == null || _aadharCardPath == null || _photoPath == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Row(
              children: [
                Icon(Icons.warning, color: Colors.white),
                SizedBox(width: 8),
                Text('Please upload all required documents'),
              ],
            ),
            backgroundColor: Colors.orange,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );
        return;
      }

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.white),
              SizedBox(width: 8),
              Text('Student added successfully! 🎉'),
            ],
          ),
          backgroundColor: Colors.green,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      );

      // TODO: Implement API call to save student data
      // For now, just clear the form
      _clearForm();
    }
  }

  void _clearForm() {
    _nameController.clear();
    _fatherNameController.clear();
    _motherNameController.clear();
    _dobController.clear();
    _classController.clear();
    _rollNumberController.clear();
    _addressController.clear();
    _phoneController.clear();
    setState(() {
      _birthCertificatePath = null;
      _aadharCardPath = null;
      _photoPath = null;
    });
  }

  @override
  void dispose() {
    _nameController.dispose();
    _fatherNameController.dispose();
    _motherNameController.dispose();
    _dobController.dispose();
    _classController.dispose();
    _rollNumberController.dispose();
    _addressController.dispose();
    _phoneController.dispose();
    super.dispose();
  }
}