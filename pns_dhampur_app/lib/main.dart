import 'package:flutter/material.dart';
import 'pages/login_page.dart';

void main() {
  runApp(PNSDhampurApp());
}

class PNSDhampurApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'PNS Dhampur',
      theme: ThemeData(primarySwatch: Colors.blue),
      debugShowCheckedModeBanner: false,
      home: LoginPage(),
    );
  }
}
