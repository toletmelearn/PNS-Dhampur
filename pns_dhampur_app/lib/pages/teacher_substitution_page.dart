import 'package:flutter/material.dart';
import '../api/api_service.dart';
import '../models/teacher_substitution.dart';

class TeacherSubstitutionPage extends StatefulWidget {
  final String token;
  final String userRole;

  const TeacherSubstitutionPage({
    Key? key,
    required this.token,
    required this.userRole,
  }) : super(key: key);

  @override
  _TeacherSubstitutionPageState createState() => _TeacherSubstitutionPageState();
}

class _TeacherSubstitutionPageState extends State<TeacherSubstitutionPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<TeacherSubstitution> _substitutions = [];
  SubstitutionStats? _stats;
  bool _isLoading = true;
  String _selectedStatus = 'all';
  String _selectedPriority = 'all';
  bool _showEmergencyOnly = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // Load substitutions and stats in parallel
      final futures = await Future.wait([
        _loadSubstitutions(),
        _loadStats(),
      ]);
    } catch (e) {
      _showErrorSnackBar('Failed to load data: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadSubstitutions() async {
    try {
      final response = await ApiService.getSubstitutions(
        status: _selectedStatus == 'all' ? null : _selectedStatus,
        priority: _selectedPriority == 'all' ? null : _selectedPriority,
        isEmergency: _showEmergencyOnly ? true : null,
        token: widget.token,
      );

      if (response['success'] == true) {
        final List<dynamic> data = response['data'];
        setState(() {
          _substitutions = data.map((json) => TeacherSubstitution.fromJson(json)).toList();
        });
      }
    } catch (e) {
      _showErrorSnackBar('Failed to load substitutions: $e');
    }
  }

  Future<void> _loadStats() async {
    try {
      final response = await ApiService.getSubstitutionStats(token: widget.token);
      if (response['success'] == true) {
        setState(() {
          _stats = SubstitutionStats.fromJson(response['data']);
        });
      }
    } catch (e) {
      _showErrorSnackBar('Failed to load stats: $e');
    }
  }

  Future<void> _autoAssignSubstitutes() async {
    try {
      final response = await ApiService.autoAssignSubstitutes(
        emergencyOnly: _showEmergencyOnly,
        token: widget.token,
      );

      if (response['success'] == true) {
        final assigned = response['data']['assigned'];
        final failed = response['data']['failed'];
        
        _showSuccessSnackBar(
          'Auto-assignment completed: $assigned assigned, $failed failed'
        );
        _loadData(); // Refresh data
      }
    } catch (e) {
      _showErrorSnackBar('Failed to auto-assign substitutes: $e');
    }
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Teacher Substitutions'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Dashboard'),
            Tab(text: 'Substitutions'),
            Tab(text: 'Create Request'),
          ],
        ),
        actions: [
          if (widget.userRole == 'admin' || widget.userRole == 'principal')
            IconButton(
              icon: const Icon(Icons.auto_fix_high),
              onPressed: _autoAssignSubstitutes,
              tooltip: 'Auto-assign substitutes',
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildDashboardTab(),
                _buildSubstitutionsTab(),
                _buildCreateRequestTab(),
              ],
            ),
    );
  }

  Widget _buildDashboardTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_stats != null) ...[
            _buildStatsCards(),
            const SizedBox(height: 20),
          ],
          _buildTodaySubstitutions(),
          const SizedBox(height: 20),
          _buildEmergencySubstitutions(),
        ],
      ),
    );
  }

  Widget _buildStatsCards() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                'Today',
                _stats!.today.values.fold(0, (sum, count) => sum + count),
                Colors.blue,
                Icons.today,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _buildStatCard(
                'This Week',
                _stats!.thisWeek.values.fold(0, (sum, count) => sum + count),
                Colors.green,
                Icons.calendar_view_week,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                'Overdue',
                _stats!.overdue,
                Colors.red,
                Icons.warning,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _buildStatCard(
                'Pending',
                _stats!.today['pending'] ?? 0,
                Colors.orange,
                Icons.pending,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard(String title, int count, Color color, IconData icon) {
    return Card(
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Icon(icon, size: 32, color: color),
            const SizedBox(height: 8),
            Text(
              count.toString(),
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            Text(
              title,
              style: const TextStyle(
                fontSize: 14,
                color: Colors.grey,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTodaySubstitutions() {
    final todaySubstitutions = _substitutions.where((s) => s.isToday).toList();
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Today\'s Substitutions',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            if (todaySubstitutions.isEmpty)
              const Text('No substitutions for today')
            else
              ...todaySubstitutions.take(5).map((substitution) =>
                  _buildSubstitutionListItem(substitution)),
            if (todaySubstitutions.length > 5)
              TextButton(
                onPressed: () => _tabController.animateTo(1),
                child: const Text('View all'),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmergencySubstitutions() {
    final emergencySubstitutions = _substitutions
        .where((s) => s.isEmergency && s.isPending)
        .toList();
    
    if (emergencySubstitutions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Card(
      color: Colors.red.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.emergency, color: Colors.red),
                const SizedBox(width: 8),
                const Text(
                  'Emergency Substitutions',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.red,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...emergencySubstitutions.map((substitution) =>
                _buildSubstitutionListItem(substitution)),
          ],
        ),
      ),
    );
  }

  Widget _buildSubstitutionsTab() {
    return Column(
      children: [
        _buildFilters(),
        Expanded(
          child: _substitutions.isEmpty
              ? const Center(child: Text('No substitutions found'))
              : ListView.builder(
                  itemCount: _substitutions.length,
                  itemBuilder: (context, index) {
                    return _buildSubstitutionCard(_substitutions[index]);
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.all(16.0),
      color: Colors.grey.shade100,
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedStatus,
                  decoration: const InputDecoration(
                    labelText: 'Status',
                    border: OutlineInputBorder(),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'all', child: Text('All Status')),
                    DropdownMenuItem(value: 'pending', child: Text('Pending')),
                    DropdownMenuItem(value: 'assigned', child: Text('Assigned')),
                    DropdownMenuItem(value: 'completed', child: Text('Completed')),
                    DropdownMenuItem(value: 'cancelled', child: Text('Cancelled')),
                  ],
                  onChanged: (value) {
                    setState(() {
                      _selectedStatus = value!;
                    });
                    _loadSubstitutions();
                  },
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _selectedPriority,
                  decoration: const InputDecoration(
                    labelText: 'Priority',
                    border: OutlineInputBorder(),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'all', child: Text('All Priority')),
                    DropdownMenuItem(value: 'low', child: Text('Low')),
                    DropdownMenuItem(value: 'medium', child: Text('Medium')),
                    DropdownMenuItem(value: 'high', child: Text('High')),
                  ],
                  onChanged: (value) {
                    setState(() {
                      _selectedPriority = value!;
                    });
                    _loadSubstitutions();
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Checkbox(
                value: _showEmergencyOnly,
                onChanged: (value) {
                  setState(() {
                    _showEmergencyOnly = value!;
                  });
                  _loadSubstitutions();
                },
              ),
              const Text('Emergency only'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSubstitutionCard(TeacherSubstitution substitution) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    substitution.absentTeacher?.name ?? 'Unknown Teacher',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                _buildStatusChip(substitution.status),
                if (substitution.isEmergency)
                  Container(
                    margin: const EdgeInsets.only(left: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Text(
                      'EMERGENCY',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.class_, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(substitution.classModel?.displayName ?? 'Unknown Class'),
                const SizedBox(width: 16),
                Icon(Icons.schedule, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(substitution.formattedTimeRange),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(substitution.formattedDate),
                const SizedBox(width: 16),
                Icon(Icons.priority_high, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(substitution.priorityDisplayName),
              ],
            ),
            if (substitution.subject != null) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  Icon(Icons.book, size: 16, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(substitution.subject!),
                ],
              ),
            ],
            if (substitution.substituteTeacher != null) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.person, color: Colors.green),
                    const SizedBox(width: 8),
                    Text(
                      'Substitute: ${substitution.substituteTeacher!.name}',
                      style: TextStyle(color: Colors.green.shade700),
                    ),
                  ],
                ),
              ),
            ],
            if (substitution.reason != null) ...[
              const SizedBox(height: 8),
              Text(
                'Reason: ${substitution.reason}',
                style: const TextStyle(fontStyle: FontStyle.italic),
              ),
            ],
            if (substitution.isPending && 
                (widget.userRole == 'admin' || widget.userRole == 'principal')) ...[
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => _showAssignSubstituteDialog(substitution),
                    child: const Text('Assign Substitute'),
                  ),
                  const SizedBox(width: 8),
                  TextButton(
                    onPressed: () => _updateSubstitutionStatus(substitution.id, 'cancelled'),
                    style: TextButton.styleFrom(foregroundColor: Colors.red),
                    child: const Text('Cancel'),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSubstitutionListItem(TeacherSubstitution substitution) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: substitution.isEmergency ? Colors.red : Colors.blue,
        child: Icon(
          substitution.isEmergency ? Icons.emergency : Icons.person,
          color: Colors.white,
        ),
      ),
      title: Text(substitution.absentTeacher?.name ?? 'Unknown Teacher'),
      subtitle: Text(
        '${substitution.classModel?.displayName ?? 'Unknown Class'} • ${substitution.formattedTimeRange}',
      ),
      trailing: _buildStatusChip(substitution.status),
      onTap: () => _showSubstitutionDetails(substitution),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    switch (status) {
      case 'pending':
        color = Colors.orange;
        break;
      case 'assigned':
        color = Colors.blue;
        break;
      case 'completed':
        color = Colors.green;
        break;
      case 'cancelled':
        color = Colors.red;
        break;
      default:
        color = Colors.grey;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        status.toUpperCase(),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _buildCreateRequestTab() {
    return const Center(
      child: Text('Create Request form will be implemented here'),
    );
  }

  void _showSubstitutionDetails(TeacherSubstitution substitution) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Substitution Details'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              _buildDetailRow('Absent Teacher', substitution.absentTeacher?.name ?? 'Unknown'),
              _buildDetailRow('Class', substitution.classModel?.displayName ?? 'Unknown'),
              _buildDetailRow('Date', substitution.formattedDate),
              _buildDetailRow('Time', substitution.formattedTimeRange),
              _buildDetailRow('Duration', substitution.durationInHours),
              if (substitution.subject != null)
                _buildDetailRow('Subject', substitution.subject!),
              _buildDetailRow('Status', substitution.statusDisplayName),
              _buildDetailRow('Priority', substitution.priorityDisplayName),
              if (substitution.isEmergency)
                _buildDetailRow('Emergency', 'Yes'),
              if (substitution.substituteTeacher != null)
                _buildDetailRow('Substitute Teacher', substitution.substituteTeacher!.name),
              if (substitution.reason != null)
                _buildDetailRow('Reason', substitution.reason!),
              if (substitution.notes != null)
                _buildDetailRow('Notes', substitution.notes!),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Close'),
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
            width: 100,
            child: Text(
              '$label:',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }

  void _showAssignSubstituteDialog(TeacherSubstitution substitution) {
    showDialog(
      context: context,
      builder: (context) => AssignSubstituteDialog(
        substitution: substitution,
        token: widget.token,
        onAssigned: () {
          _loadData();
          Navigator.of(context).pop();
        },
      ),
    );
  }

  Future<void> _updateSubstitutionStatus(int substitutionId, String status) async {
    try {
      final response = await ApiService.updateSubstitutionStatus(
        substitutionId: substitutionId,
        status: status,
        token: widget.token,
      );

      if (response['success'] == true) {
        _showSuccessSnackBar('Substitution $status successfully');
        _loadData();
      }
    } catch (e) {
      _showErrorSnackBar('Failed to update substitution: $e');
    }
  }
}

class AssignSubstituteDialog extends StatefulWidget {
  final TeacherSubstitution substitution;
  final String token;
  final VoidCallback onAssigned;

  const AssignSubstituteDialog({
    Key? key,
    required this.substitution,
    required this.token,
    required this.onAssigned,
  }) : super(key: key);

  @override
  _AssignSubstituteDialogState createState() => _AssignSubstituteDialogState();
}

class _AssignSubstituteDialogState extends State<AssignSubstituteDialog> {
  List<AvailableSubstitute> _availableSubstitutes = [];
  bool _isLoading = true;
  AvailableSubstitute? _selectedSubstitute;

  @override
  void initState() {
    super.initState();
    _loadAvailableSubstitutes();
  }

  Future<void> _loadAvailableSubstitutes() async {
    try {
      final response = await ApiService.getAvailableSubstitutes(
        date: widget.substitution.date,
        startTime: widget.substitution.startTime,
        endTime: widget.substitution.endTime,
        subject: widget.substitution.subject,
        token: widget.token,
      );

      if (response['success'] == true) {
        final List<dynamic> data = response['data'];
        setState(() {
          _availableSubstitutes = data
              .map((json) => AvailableSubstitute.fromJson(json))
              .toList();
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to load available substitutes: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _assignSubstitute() async {
    if (_selectedSubstitute == null) return;

    try {
      final response = await ApiService.assignSubstitute(
        substitutionId: widget.substitution.id,
        substituteTeacherId: _selectedSubstitute!.id,
        token: widget.token,
      );

      if (response['success'] == true) {
        widget.onAssigned();
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to assign substitute: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Assign Substitute Teacher'),
      content: SizedBox(
        width: double.maxFinite,
        height: 400,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _availableSubstitutes.isEmpty
                ? const Center(child: Text('No available substitutes found'))
                : Column(
                    children: [
                      Text(
                        'Select a substitute for ${widget.substitution.formattedDate} ${widget.substitution.formattedTimeRange}',
                        style: const TextStyle(fontSize: 14),
                      ),
                      const SizedBox(height: 16),
                      Expanded(
                        child: ListView.builder(
                          itemCount: _availableSubstitutes.length,
                          itemBuilder: (context, index) {
                            final substitute = _availableSubstitutes[index];
                            return RadioListTile<AvailableSubstitute>(
                              value: substitute,
                              groupValue: _selectedSubstitute,
                              onChanged: (value) {
                                setState(() {
                                  _selectedSubstitute = value;
                                });
                              },
                              title: Text(substitute.name),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(substitute.email),
                                  Text(substitute.workloadText),
                                  if (substitute.subjectExpertise != null)
                                    Text('Expertise: ${substitute.subjectExpertise!.join(', ')}'),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        ElevatedButton(
          onPressed: _selectedSubstitute != null ? _assignSubstitute : null,
          child: const Text('Assign'),
        ),
      ],
    );
  }
}