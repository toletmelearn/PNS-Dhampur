import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = "http://localhost:8000/api";

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {"Content-Type": "application/json"},
      body: jsonEncode({"email": email, "password": password}),
    )

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Login failed: ${response.body}');
    }
  }
}
// pay fee
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

// get receipt url (open it in browser)
static String receiptUrl(int feeId) {
  return '$baseUrl/fees/$feeId/receipt';
}

// after login, you got token
final token = '<token-from-login>';

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
