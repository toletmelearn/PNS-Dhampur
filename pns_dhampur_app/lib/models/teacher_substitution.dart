class TeacherSubstitution {
  final int id;
  final int absentTeacherId;
  final int? substituteTeacherId;
  final int classId;
  final String date;
  final String startTime;
  final String endTime;
  final String? subject;
  final String status;
  final String? reason;
  final String? notes;
  final String priority;
  final bool isEmergency;
  final DateTime requestedAt;
  final int requestedBy;
  final int? assignedBy;
  final DateTime? assignedAt;
  final DateTime? completedAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  // Related objects
  final Teacher? absentTeacher;
  final Teacher? substituteTeacher;
  final ClassModel? classModel;
  final User? requestedByUser;
  final User? assignedByUser;

  TeacherSubstitution({
    required this.id,
    required this.absentTeacherId,
    this.substituteTeacherId,
    required this.classId,
    required this.date,
    required this.startTime,
    required this.endTime,
    this.subject,
    required this.status,
    this.reason,
    this.notes,
    required this.priority,
    required this.isEmergency,
    required this.requestedAt,
    required this.requestedBy,
    this.assignedBy,
    this.assignedAt,
    this.completedAt,
    required this.createdAt,
    required this.updatedAt,
    this.absentTeacher,
    this.substituteTeacher,
    this.classModel,
    this.requestedByUser,
    this.assignedByUser,
  });

  factory TeacherSubstitution.fromJson(Map<String, dynamic> json) {
    return TeacherSubstitution(
      id: json['id'],
      absentTeacherId: json['absent_teacher_id'],
      substituteTeacherId: json['substitute_teacher_id'],
      classId: json['class_id'],
      date: json['date'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      subject: json['subject'],
      status: json['status'],
      reason: json['reason'],
      notes: json['notes'],
      priority: json['priority'],
      isEmergency: json['is_emergency'] ?? false,
      requestedAt: DateTime.parse(json['requested_at']),
      requestedBy: json['requested_by'],
      assignedBy: json['assigned_by'],
      assignedAt: json['assigned_at'] != null ? DateTime.parse(json['assigned_at']) : null,
      completedAt: json['completed_at'] != null ? DateTime.parse(json['completed_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      absentTeacher: json['absent_teacher'] != null ? Teacher.fromJson(json['absent_teacher']) : null,
      substituteTeacher: json['substitute_teacher'] != null ? Teacher.fromJson(json['substitute_teacher']) : null,
      classModel: json['class'] != null ? ClassModel.fromJson(json['class']) : null,
      requestedByUser: json['requested_by_user'] != null ? User.fromJson(json['requested_by_user']) : null,
      assignedByUser: json['assigned_by_user'] != null ? User.fromJson(json['assigned_by_user']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'absent_teacher_id': absentTeacherId,
      'substitute_teacher_id': substituteTeacherId,
      'class_id': classId,
      'date': date,
      'start_time': startTime,
      'end_time': endTime,
      'subject': subject,
      'status': status,
      'reason': reason,
      'notes': notes,
      'priority': priority,
      'is_emergency': isEmergency,
      'requested_at': requestedAt.toIso8601String(),
      'requested_by': requestedBy,
      'assigned_by': assignedBy,
      'assigned_at': assignedAt?.toIso8601String(),
      'completed_at': completedAt?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Helper methods
  String get formattedDate {
    final dateTime = DateTime.parse(date);
    return '${dateTime.day}/${dateTime.month}/${dateTime.year}';
  }

  String get formattedTimeRange {
    return '$startTime - $endTime';
  }

  String get statusDisplayName {
    switch (status) {
      case 'pending':
        return 'Pending';
      case 'assigned':
        return 'Assigned';
      case 'completed':
        return 'Completed';
      case 'cancelled':
        return 'Cancelled';
      default:
        return status.toUpperCase();
    }
  }

  String get priorityDisplayName {
    switch (priority) {
      case 'low':
        return 'Low';
      case 'medium':
        return 'Medium';
      case 'high':
        return 'High';
      default:
        return priority.toUpperCase();
    }
  }

  String get durationInHours {
    final start = DateTime.parse('2000-01-01 $startTime:00');
    final end = DateTime.parse('2000-01-01 $endTime:00');
    final duration = end.difference(start);
    final hours = duration.inMinutes / 60;
    return '${hours.toStringAsFixed(1)}h';
  }

  bool get isPending => status == 'pending';
  bool get isAssigned => status == 'assigned';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';

  bool get isOverdue {
    if (status != 'pending') return false;
    final requestDate = DateTime.parse(date);
    return requestDate.isBefore(DateTime.now());
  }

  bool get isToday {
    final requestDate = DateTime.parse(date);
    final today = DateTime.now();
    return requestDate.year == today.year &&
           requestDate.month == today.month &&
           requestDate.day == today.day;
  }

  bool get isUpcoming {
    final requestDate = DateTime.parse(date);
    return requestDate.isAfter(DateTime.now());
  }
}

class Teacher {
  final int id;
  final String name;
  final String email;
  final String? qualification;
  final int? experienceYears;

  Teacher({
    required this.id,
    required this.name,
    required this.email,
    this.qualification,
    this.experienceYears,
  });

  factory Teacher.fromJson(Map<String, dynamic> json) {
    return Teacher(
      id: json['id'],
      name: json['user']?['name'] ?? json['name'] ?? '',
      email: json['user']?['email'] ?? json['email'] ?? '',
      qualification: json['qualification'],
      experienceYears: json['experience_years'],
    );
  }
}

class ClassModel {
  final int id;
  final String name;
  final String? section;

  ClassModel({
    required this.id,
    required this.name,
    this.section,
  });

  factory ClassModel.fromJson(Map<String, dynamic> json) {
    return ClassModel(
      id: json['id'],
      name: json['name'],
      section: json['section'],
    );
  }

  String get displayName {
    return section != null ? '$name - $section' : name;
  }
}

class User {
  final int id;
  final String name;
  final String email;

  User({
    required this.id,
    required this.name,
    required this.email,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
    );
  }
}

class SubstitutionStats {
  final Map<String, int> today;
  final Map<String, int> thisWeek;
  final int overdue;

  SubstitutionStats({
    required this.today,
    required this.thisWeek,
    required this.overdue,
  });

  factory SubstitutionStats.fromJson(Map<String, dynamic> json) {
    return SubstitutionStats(
      today: Map<String, int>.from(json['today']),
      thisWeek: Map<String, int>.from(json['this_week']),
      overdue: json['overdue'],
    );
  }
}

class AvailableSubstitute {
  final int id;
  final String name;
  final String email;
  final int? experienceYears;
  final String? qualification;
  final List<String>? subjectExpertise;
  final int currentSubstitutions;
  final int maxSubstitutions;
  final bool canTakeMore;

  AvailableSubstitute({
    required this.id,
    required this.name,
    required this.email,
    this.experienceYears,
    this.qualification,
    this.subjectExpertise,
    required this.currentSubstitutions,
    required this.maxSubstitutions,
    required this.canTakeMore,
  });

  factory AvailableSubstitute.fromJson(Map<String, dynamic> json) {
    return AvailableSubstitute(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      experienceYears: json['experience_years'],
      qualification: json['qualification'],
      subjectExpertise: json['subject_expertise'] != null 
          ? List<String>.from(json['subject_expertise'])
          : null,
      currentSubstitutions: json['current_substitutions'],
      maxSubstitutions: json['max_substitutions'],
      canTakeMore: json['can_take_more'],
    );
  }

  String get workloadText {
    return '$currentSubstitutions/$maxSubstitutions substitutions today';
  }
}