import 'package:flutter/material.dart';
import 'bell_schedule_page.dart';
import 'teacher_substitution_page.dart';

class DashboardPage extends StatelessWidget {
  final String token;
  final String userRole;

  const DashboardPage({
    Key? key,
    required this.token,
    required this.userRole,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('PNS Dhampur Dashboard'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () {
              Navigator.of(context).pushReplacementNamed('/login');
            },
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: GridView.count(
          crossAxisCount: 2,
          crossAxisSpacing: 16,
          mainAxisSpacing: 16,
          children: [
            // Bell Schedule
            _buildDashboardCard(
              context,
              title: 'Bell Schedule',
              icon: Icons.schedule,
              color: Colors.blue,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => BellSchedulePage(token: token),
                  ),
                );
              },
            ),
            
            // Teacher Substitution
            _buildDashboardCard(
              context,
              title: 'Teacher Substitution',
              icon: Icons.swap_horiz,
              color: Colors.green,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => TeacherSubstitutionPage(
                      token: token,
                      userRole: userRole,
                    ),
                  ),
                );
              },
            ),
            
            // Student Management
            _buildDashboardCard(
              context,
              title: 'Students',
              icon: Icons.school,
              color: Colors.orange,
              onTap: () {
                // TODO: Navigate to student management
                _showComingSoon(context, 'Student Management');
              },
            ),
            
            // Teacher Management
            _buildDashboardCard(
              context,
              title: 'Teachers',
              icon: Icons.person,
              color: Colors.purple,
              onTap: () {
                // TODO: Navigate to teacher management
                _showComingSoon(context, 'Teacher Management');
              },
            ),
            
            // Attendance
            _buildDashboardCard(
              context,
              title: 'Attendance',
              icon: Icons.check_circle,
              color: Colors.teal,
              onTap: () {
                // TODO: Navigate to attendance
                _showComingSoon(context, 'Attendance Management');
              },
            ),
            
            // Fee Management
            _buildDashboardCard(
              context,
              title: 'Fee Management',
              icon: Icons.payment,
              color: Colors.indigo,
              onTap: () {
                // TODO: Navigate to fee management
                _showComingSoon(context, 'Fee Management');
              },
            ),
            
            // Exam & Results
            _buildDashboardCard(
              context,
              title: 'Exam Management',
              icon: Icons.quiz,
              color: Colors.red,
              onTap: () {
                // TODO: Navigate to exam management
                _showComingSoon(context, 'Exam & Results');
              },
            ),
            
            // Inventory
            _buildDashboardCard(
              context,
              title: 'Inventory',
              icon: Icons.inventory,
              color: Colors.brown,
              onTap: () {
                // TODO: Navigate to inventory
                _showComingSoon(context, 'Inventory Management');
              },
            ),
            
            // Budget
            _buildDashboardCard(
              context,
              title: 'Budget Management',
              icon: Icons.account_balance,
              color: Colors.cyan,
              onTap: () {
                // TODO: Navigate to budget
                _showComingSoon(context, 'Budget Management');
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDashboardCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 4,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                color.withOpacity(0.1),
                color.withOpacity(0.05),
              ],
            ),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 48,
                color: color,
              ),
              const SizedBox(height: 12),
              Text(
                title,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: color.withOpacity(0.8),
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showComingSoon(BuildContext context, String feature) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Coming Soon'),
          content: Text('$feature feature is under development.'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
              },
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }
}