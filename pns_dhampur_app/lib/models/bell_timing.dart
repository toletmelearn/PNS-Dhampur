class BellTiming {
  final int id;
  final String name;
  final String time;
  final String season;
  final String type;
  final String? description;
  final bool isActive;
  final int order;
  final DateTime createdAt;
  final DateTime updatedAt;

  BellTiming({
    required this.id,
    required this.name,
    required this.time,
    required this.season,
    required this.type,
    this.description,
    required this.isActive,
    required this.order,
    required this.createdAt,
    required this.updatedAt,
  });

  factory BellTiming.fromJson(Map<String, dynamic> json) {
    return BellTiming(
      id: json['id'],
      name: json['name'],
      time: json['time'],
      season: json['season'],
      type: json['type'],
      description: json['description'],
      isActive: json['is_active'] ?? true,
      order: json['order'] ?? 0,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'time': time,
      'season': season,
      'type': type,
      'description': description,
      'is_active': isActive,
      'order': order,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  String get formattedTime {
    // Convert 24-hour format to 12-hour format
    final parts = time.split(':');
    final hour = int.parse(parts[0]);
    final minute = parts[1];
    
    if (hour == 0) {
      return '12:$minute AM';
    } else if (hour < 12) {
      return '$hour:$minute AM';
    } else if (hour == 12) {
      return '12:$minute PM';
    } else {
      return '${hour - 12}:$minute PM';
    }
  }

  String get typeIcon {
    switch (type) {
      case 'start':
        return '🟢';
      case 'end':
        return '🔴';
      case 'break':
        return '🟡';
      default:
        return '🔔';
    }
  }

  String get typeDescription {
    switch (type) {
      case 'start':
        return 'Start';
      case 'end':
        return 'End';
      case 'break':
        return 'Break';
      default:
        return 'Bell';
    }
  }
}