import 'package:flutter/material.dart';

class BellTimingPage extends StatefulWidget {
  final String token;

  const BellTimingPage({Key? key, required this.token}) : super(key: key);

  @override
  State<BellTimingPage> createState() => _BellTimingPageState();
}

class _BellTimingPageState extends State<BellTimingPage> with TickerProviderStateMixin {
  late TabController _tabController;
  bool _notificationsEnabled = true;
  String _selectedSeason = 'Summer';

  // Sample bell timing data
  final Map<String, List<Map<String, dynamic>>> _bellTimings = {
    'Summer': [
      {'time': '07:30 AM', 'activity': 'School Opens', 'icon': Icons.school, 'emoji': '🏫'},
      {'time': '08:00 AM', 'activity': 'Morning Assembly', 'icon': Icons.groups, 'emoji': '🙏'},
      {'time': '08:15 AM', 'activity': '1st Period', 'icon': Icons.book, 'emoji': '📚'},
      {'time': '09:00 AM', 'activity': '2nd Period', 'icon': Icons.book, 'emoji': '📖'},
      {'time': '09:45 AM', 'activity': '3rd Period', 'icon': Icons.book, 'emoji': '📝'},
      {'time': '10:30 AM', 'activity': 'Break Time', 'icon': Icons.free_breakfast, 'emoji': '🍎'},
      {'time': '10:45 AM', 'activity': '4th Period', 'icon': Icons.book, 'emoji': '📊'},
      {'time': '11:30 AM', 'activity': '5th Period', 'icon': Icons.book, 'emoji': '🔬'},
      {'time': '12:15 PM', 'activity': 'Lunch Break', 'icon': Icons.lunch_dining, 'emoji': '🍽️'},
      {'time': '01:00 PM', 'activity': '6th Period', 'icon': Icons.book, 'emoji': '🎨'},
      {'time': '01:45 PM', 'activity': '7th Period', 'icon': Icons.book, 'emoji': '⚽'},
      {'time': '02:30 PM', 'activity': 'School Ends', 'icon': Icons.home, 'emoji': '🏠'},
    ],
    'Winter': [
      {'time': '08:00 AM', 'activity': 'School Opens', 'icon': Icons.school, 'emoji': '🏫'},
      {'time': '08:30 AM', 'activity': 'Morning Assembly', 'icon': Icons.groups, 'emoji': '🙏'},
      {'time': '08:45 AM', 'activity': '1st Period', 'icon': Icons.book, 'emoji': '📚'},
      {'time': '09:30 AM', 'activity': '2nd Period', 'icon': Icons.book, 'emoji': '📖'},
      {'time': '10:15 AM', 'activity': '3rd Period', 'icon': Icons.book, 'emoji': '📝'},
      {'time': '11:00 AM', 'activity': 'Break Time', 'icon': Icons.free_breakfast, 'emoji': '🍎'},
      {'time': '11:15 AM', 'activity': '4th Period', 'icon': Icons.book, 'emoji': '📊'},
      {'time': '12:00 PM', 'activity': '5th Period', 'icon': Icons.book, 'emoji': '🔬'},
      {'time': '12:45 PM', 'activity': 'Lunch Break', 'icon': Icons.lunch_dining, 'emoji': '🍽️'},
      {'time': '01:30 PM', 'activity': '6th Period', 'icon': Icons.book, 'emoji': '🎨'},
      {'time': '02:15 PM', 'activity': '7th Period', 'icon': Icons.book, 'emoji': '⚽'},
      {'time': '03:00 PM', 'activity': 'School Ends', 'icon': Icons.home, 'emoji': '🏠'},
    ],
  };

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
              child: const Text('🔔', style: TextStyle(fontSize: 20)),
            ),
            const SizedBox(width: 12),
            const Text(
              'Bell Timing',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFFFF5722),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            onPressed: _toggleNotifications,
            icon: Icon(
              _notificationsEnabled ? Icons.notifications_active : Icons.notifications_off,
              color: Colors.white,
            ),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: '⏰ Schedule'),
            Tab(text: '⚙️ Settings'),
            Tab(text: '📊 Analytics'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildScheduleTab(),
          _buildSettingsTab(),
          _buildAnalyticsTab(),
        ],
      ),
    );
  }

  Widget _buildScheduleTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Season Selector
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: _selectedSeason == 'Summer' 
                    ? [const Color(0xFFFF9800), const Color(0xFFFFB74D)]
                    : [const Color(0xFF2196F3), const Color(0xFF64B5F6)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      _selectedSeason == 'Summer' ? '☀️' : '❄️',
                      style: const TextStyle(fontSize: 32),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '$_selectedSeason Schedule',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            'Current active schedule',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.9),
                              fontSize: 16,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Switch(
                      value: _selectedSeason == 'Summer',
                      onChanged: (value) {
                        setState(() {
                          _selectedSeason = value ? 'Summer' : 'Winter';
                        });
                      },
                      activeColor: Colors.white,
                      activeTrackColor: Colors.white.withOpacity(0.3),
                      inactiveThumbColor: Colors.white,
                      inactiveTrackColor: Colors.white.withOpacity(0.3),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Current Time Status
          _buildCurrentTimeStatus(),
          const SizedBox(height: 24),

          // Bell Schedule
          Text(
            '📅 Today\'s Schedule',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 16),

          ..._bellTimings[_selectedSeason]!.map((timing) => _buildTimingCard(timing)).toList(),
        ],
      ),
    );
  }

  Widget _buildSettingsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Notification Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '🔔 Notification Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildSettingItem(
                    title: 'Bell Notifications',
                    subtitle: 'Get notified when bell rings',
                    value: _notificationsEnabled,
                    onChanged: (value) {
                      setState(() {
                        _notificationsEnabled = value;
                      });
                    },
                  ),
                  const Divider(),
                  _buildSettingItem(
                    title: 'Break Reminders',
                    subtitle: '5 minutes before break time',
                    value: true,
                    onChanged: (value) {},
                  ),
                  const Divider(),
                  _buildSettingItem(
                    title: 'Period Change Alert',
                    subtitle: 'Alert when period changes',
                    value: true,
                    onChanged: (value) {},
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Sound Settings
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '🔊 Sound Settings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.volume_up, color: Color(0xFFFF5722)),
                    title: const Text('Bell Sound'),
                    subtitle: const Text('Classic School Bell'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _showSoundSelector(),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.vibration, color: Color(0xFFFF5722)),
                    title: const Text('Vibration'),
                    subtitle: const Text('Vibrate on bell ring'),
                    trailing: Switch(
                      value: true,
                      onChanged: (value) {},
                      activeColor: const Color(0xFFFF5722),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Schedule Management
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📅 Schedule Management',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ListTile(
                    leading: const Icon(Icons.edit, color: Color(0xFFFF5722)),
                    title: const Text('Edit Summer Schedule'),
                    subtitle: const Text('Modify summer bell timings'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _editSchedule('Summer'),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.edit, color: Color(0xFFFF5722)),
                    title: const Text('Edit Winter Schedule'),
                    subtitle: const Text('Modify winter bell timings'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _editSchedule('Winter'),
                  ),
                  const Divider(),
                  ListTile(
                    leading: const Icon(Icons.add, color: Color(0xFFFF5722)),
                    title: const Text('Add Custom Schedule'),
                    subtitle: const Text('Create special day schedule'),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: () => _addCustomSchedule(),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAnalyticsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Summary Cards
          Row(
            children: [
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Total Periods',
                  value: '7',
                  icon: Icons.schedule,
                  color: const Color(0xFF2196F3),
                  emoji: '📚',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Break Time',
                  value: '45 min',
                  icon: Icons.free_breakfast,
                  color: const Color(0xFF4CAF50),
                  emoji: '🍎',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'School Hours',
                  value: _selectedSeason == 'Summer' ? '7 hrs' : '7 hrs',
                  icon: Icons.access_time,
                  color: const Color(0xFFFF5722),
                  emoji: '⏰',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildAnalyticsCard(
                  title: 'Active Season',
                  value: _selectedSeason,
                  icon: Icons.wb_sunny,
                  color: const Color(0xFF9C27B0),
                  emoji: _selectedSeason == 'Summer' ? '☀️' : '❄️',
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Time Distribution Chart
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📊 Time Distribution',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildTimeDistributionItem('Classes', '5.25 hrs', 0.75, const Color(0xFF2196F3)),
                  const SizedBox(height: 12),
                  _buildTimeDistributionItem('Breaks', '1.25 hrs', 0.18, const Color(0xFF4CAF50)),
                  const SizedBox(height: 12),
                  _buildTimeDistributionItem('Assembly', '0.25 hrs', 0.04, const Color(0xFFFF5722)),
                  const SizedBox(height: 12),
                  _buildTimeDistributionItem('Other', '0.25 hrs', 0.03, const Color(0xFF9C27B0)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Weekly Schedule Overview
          Card(
            elevation: 8,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📅 Weekly Overview',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ...['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].map(
                    (day) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Row(
                        children: [
                          Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFF5722).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Center(
                              child: Text('📅', style: TextStyle(fontSize: 16)),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  day,
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                Text(
                                  '7 periods • ${_selectedSeason} schedule',
                                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
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
                            child: const Text(
                              'Active',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ).toList(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCurrentTimeStatus() {
    final now = DateTime.now();
    final currentTime = '${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}';
    
    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFF6C63FF), Color(0xFF9C88FF)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.access_time,
                color: Colors.white,
                size: 32,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Current Time',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    currentTime,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'Next: 3rd Period in 15 mins',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
            const Text('⏰', style: TextStyle(fontSize: 32)),
          ],
        ),
      ),
    );
  }

  Widget _buildTimingCard(Map<String, dynamic> timing) {
    final now = DateTime.now();
    final isCurrentPeriod = false; // This would be calculated based on current time
    
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: isCurrentPeriod ? 12 : 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: isCurrentPeriod 
              ? Border.all(color: const Color(0xFF4CAF50), width: 2)
              : null,
          gradient: isCurrentPeriod 
              ? LinearGradient(
                  colors: [const Color(0xFF4CAF50).withOpacity(0.1), Colors.white],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                )
              : null,
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isCurrentPeriod 
                    ? const Color(0xFF4CAF50).withOpacity(0.2)
                    : const Color(0xFFFF5722).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                timing['emoji'],
                style: const TextStyle(fontSize: 24),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    timing['activity'],
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: isCurrentPeriod ? const Color(0xFF4CAF50) : Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    timing['time'],
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            if (isCurrentPeriod)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF4CAF50),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text(
                  'Now',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            IconButton(
              onPressed: () => _setReminder(timing),
              icon: Icon(
                Icons.notifications_none,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSettingItem({
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(
        title,
        style: const TextStyle(fontWeight: FontWeight.bold),
      ),
      subtitle: Text(subtitle),
      trailing: Switch(
        value: value,
        onChanged: onChanged,
        activeColor: const Color(0xFFFF5722),
      ),
    );
  }

  Widget _buildAnalyticsCard({
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

  Widget _buildTimeDistributionItem(String label, String time, double percentage, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(
              time,
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
        const SizedBox(height: 8),
        LinearProgressIndicator(
          value: percentage,
          backgroundColor: Colors.grey[200],
          valueColor: AlwaysStoppedAnimation<Color>(color),
          minHeight: 8,
        ),
      ],
    );
  }

  void _toggleNotifications() {
    setState(() {
      _notificationsEnabled = !_notificationsEnabled;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _notificationsEnabled 
              ? 'Notifications enabled! 🔔' 
              : 'Notifications disabled! 🔕',
        ),
        backgroundColor: _notificationsEnabled ? Colors.green : Colors.orange,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  void _showSoundSelector() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('🔊 Select Bell Sound'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.music_note),
              title: const Text('Classic School Bell'),
              trailing: const Icon(Icons.check, color: Colors.green),
              onTap: () => Navigator.pop(context),
            ),
            ListTile(
              leading: const Icon(Icons.music_note),
              title: const Text('Modern Chime'),
              onTap: () => Navigator.pop(context),
            ),
            ListTile(
              leading: const Icon(Icons.music_note),
              title: const Text('Gentle Bell'),
              onTap: () => Navigator.pop(context),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
        ],
      ),
    );
  }

  void _editSchedule(String season) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Edit $season schedule functionality will be implemented! ✏️'),
        backgroundColor: const Color(0xFFFF5722),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  void _addCustomSchedule() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Add custom schedule functionality will be implemented! ➕'),
        backgroundColor: Color(0xFFFF5722),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _setReminder(Map<String, dynamic> timing) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Reminder set for ${timing['activity']} at ${timing['time']} ⏰'),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}