import 'package:flutter/material.dart';
import 'login_page.dart';

class HomePage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFF1565C0), // Deep Blue
              Color(0xFF0D47A1), // Darker Blue
              Color(0xFF0A2E5C), // Navy Blue
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              // Header Section
              Container(
                padding: EdgeInsets.all(20),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'PNS DHAMPUR',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 1.2,
                      ),
                    ),
                    Icon(
                      Icons.school,
                      color: Colors.white,
                      size: 32,
                    ),
                  ],
                ),
              ),
              
              // Main Content
              Expanded(
                child: SingleChildScrollView(
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: 20),
                    child: Column(
                      children: [
                        SizedBox(height: 40),
                        
                        // School Logo/Icon
                        Container(
                          width: 120,
                          height: 120,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(60),
                            border: Border.all(color: Colors.white.withOpacity(0.3), width: 2),
                          ),
                          child: Icon(
                            Icons.account_balance,
                            size: 60,
                            color: Colors.white,
                          ),
                        ),
                        
                        SizedBox(height: 30),
                        
                        // School Name & Tagline
                        Text(
                          'PNS DHAMPUR',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 32,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 2,
                          ),
                        ),
                        
                        SizedBox(height: 10),
                        
                        Text(
                          'School Management System',
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.9),
                            fontSize: 18,
                            fontWeight: FontWeight.w300,
                            letterSpacing: 1,
                          ),
                        ),
                        
                        SizedBox(height: 8),
                        
                        Text(
                          'Excellence in Education',
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.7),
                            fontSize: 14,
                            fontStyle: FontStyle.italic,
                          ),
                        ),
                        
                        SizedBox(height: 50),
                        
                        // Features Grid
                        Container(
                          padding: EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(15),
                            border: Border.all(color: Colors.white.withOpacity(0.2)),
                          ),
                          child: Column(
                            children: [
                              Text(
                                'Comprehensive School Management',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 20),
                              
                              // Feature Items
                              _buildFeatureItem(Icons.people, 'Student Management'),
                              _buildFeatureItem(Icons.person_outline, 'Teacher Management'),
                              _buildFeatureItem(Icons.payment, 'Fee Management'),
                              _buildFeatureItem(Icons.assessment, 'Academic Records'),
                              _buildFeatureItem(Icons.schedule, 'Attendance Tracking'),
                              _buildFeatureItem(Icons.inventory, 'Inventory Management'),
                            ],
                          ),
                        ),
                        
                        SizedBox(height: 40),
                        
                        // Login Button
                        Container(
                          width: double.infinity,
                          height: 55,
                          child: ElevatedButton(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (context) => LoginPage()),
                              );
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.white,
                              foregroundColor: Color(0xFF1565C0),
                              elevation: 8,
                              shadowColor: Colors.black.withOpacity(0.3),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.login, size: 24),
                                SizedBox(width: 12),
                                Text(
                                  'ACCESS SYSTEM',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 1,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        
                        SizedBox(height: 30),
                        
                        // Footer
                        Text(
                          '© 2024 PNS Dhampur School\nAll Rights Reserved',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.6),
                            fontSize: 12,
                            height: 1.5,
                          ),
                        ),
                        
                        SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  Widget _buildFeatureItem(IconData icon, String title) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              icon,
              color: Colors.white,
              size: 20,
            ),
          ),
          SizedBox(width: 15),
          Text(
            title,
            style: TextStyle(
              color: Colors.white.withOpacity(0.9),
              fontSize: 16,
              fontWeight: FontWeight.w400,
            ),
          ),
        ],
      ),
    );
  }
}