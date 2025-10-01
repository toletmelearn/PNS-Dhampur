import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = "http://localhost:8000/api";

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {"Content-Type": "application/json"},
      body: jsonEncode({"email": email, "password": password}),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Login failed: ${response.body}');
    }
  }

  // Pay fee
  static Future<Map<String, dynamic>> payFee(int feeId, double amount, {String paymentMode = 'cash', String? paidDate, String? remarks, required String token}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/fees/$feeId/pay'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'paid_amount': amount,
        'payment_mode': paymentMode,
        'paid_date': paidDate,
        'remarks': remarks
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Payment failed: ${response.body}');
    }
  }

  // Get receipt url (open it in browser)
  static String receiptUrl(int feeId) {
    return '$baseUrl/fees/$feeId/receipt';
  }

  // Bell Timing APIs
  static Future<Map<String, dynamic>> getCurrentSchedule({required String token}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/bell-timings/schedule/current'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get schedule: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> checkBellNotification({required String token}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/bell-timings/notification/check'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to check notifications: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> getBellTimings({String? season, required String token}) async {
    String url = '$baseUrl/bell-timings';
    if (season != null) {
      url += '?season=$season';
    }

    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get bell timings: ${response.body}');
    }
  }

  // Teacher Substitution APIs
  static Future<Map<String, dynamic>> getSubstitutions({
    String? status,
    String? date,
    String? priority,
    bool? isEmergency,
    required String token,
  }) async {
    String url = '$baseUrl/teacher-substitutions';
    List<String> params = [];
    
    if (status != null) params.add('status=$status');
    if (date != null) params.add('date=$date');
    if (priority != null) params.add('priority=$priority');
    if (isEmergency != null) params.add('is_emergency=${isEmergency ? 1 : 0}');
    
    if (params.isNotEmpty) {
      url += '?${params.join('&')}';
    }

    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get substitutions: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> createSubstitution({
    required int absentTeacherId,
    required int classId,
    required String date,
    required String startTime,
    required String endTime,
    String? subject,
    String? reason,
    String? notes,
    String priority = 'medium',
    bool isEmergency = false,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/teacher-substitutions'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'absent_teacher_id': absentTeacherId,
        'class_id': classId,
        'date': date,
        'start_time': startTime,
        'end_time': endTime,
        'subject': subject,
        'reason': reason,
        'notes': notes,
        'priority': priority,
        'is_emergency': isEmergency,
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to create substitution: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> getAvailableSubstitutes({
    required String date,
    required String startTime,
    required String endTime,
    String? subject,
    required String token,
  }) async {
    String url = '$baseUrl/teacher-substitutions/available-substitutes';
    List<String> params = [
      'date=$date',
      'start_time=$startTime',
      'end_time=$endTime',
    ];
    
    if (subject != null) params.add('subject=$subject');
    url += '?${params.join('&')}';

    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get available substitutes: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> assignSubstitute({
    required int substitutionId,
    required int substituteTeacherId,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/teacher-substitutions/$substitutionId/assign'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'substitute_teacher_id': substituteTeacherId,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to assign substitute: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> updateSubstitutionStatus({
    required int substitutionId,
    required String status,
    String? notes,
    required String token,
  }) async {
    final response = await http.put(
      Uri.parse('$baseUrl/teacher-substitutions/$substitutionId'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'status': status,
        'notes': notes,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to update substitution: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> getSubstitutionStats({required String token}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/teacher-substitutions/dashboard-stats'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to get substitution stats: ${response.body}');
    }
  }

  static Future<Map<String, dynamic>> autoAssignSubstitutes({
    String? date,
    bool emergencyOnly = false,
    required String token,
  }) async {
    String url = '$baseUrl/teacher-substitutions/auto-assign';
    List<String> params = [];
    
    if (date != null) params.add('date=$date');
    if (emergencyOnly) params.add('emergency_only=1');
    
    if (params.isNotEmpty) {
      url += '?${params.join('&')}';
    }

    final response = await http.post(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to auto-assign substitutes: ${response.body}');
    }
  }
}

// pay fee #5 for 500
try {
  final res = await ApiService.payFee(5, 500.0, token: token);
  print('receipt_url = ${res['receipt_url']}');
  // To open in browser (web):
  final receiptUrl = res['receipt_url'];
  // use url_launcher to open
  // but browser might require auth header — simpler: return URL from API is asset('storage/...') which is public by default
  // open it:
  import 'package:url_launcher/url_launcher.dart';
  await launch(receiptUrl);
} catch (e) {
  print('Payment error: $e');
}
