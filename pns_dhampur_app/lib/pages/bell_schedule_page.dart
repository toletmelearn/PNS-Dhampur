import 'package:flutter/material.dart';
import '../api/api_service.dart';
import '../models/bell_timing.dart';
import 'dart:async';

class BellSchedulePage extends StatefulWidget {
  final String token;

  const BellSchedulePage({Key? key, required this.token}) : super(key: key);

  @override
  State<BellSchedulePage> createState() => _BellSchedulePageState();
}

class _BellSchedulePageState extends State<BellSchedulePage> {
  List<BellTiming> schedule = [];
  BellTiming? nextBell;
  String currentSeason = '';
  bool isLoading = true;
  String? error;
  Timer? notificationTimer;

  @override
  void initState() {
    super.initState();
    loadSchedule();
    startNotificationTimer();
  }

  @override
  void dispose() {
    notificationTimer?.cancel();
    super.dispose();
  }

  void startNotificationTimer() {
    // Check for notifications every minute
    notificationTimer = Timer.periodic(const Duration(minutes: 1), (timer) {
      checkBellNotifications();
    });
  }

  Future<void> loadSchedule() async {
    try {
      setState(() {
        isLoading = true;
        error = null;
      });

      final response = await ApiService.getCurrentSchedule(token: widget.token);
      
      if (response['success']) {
        final data = response['data'];
        setState(() {
          schedule = (data['schedule'] as List)
              .map((json) => BellTiming.fromJson(json))
              .toList();
          nextBell = data['next_bell'] != null 
              ? BellTiming.fromJson(data['next_bell']) 
              : null;
          currentSeason = data['current_season'] ?? '';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        error = e.toString();
        isLoading = false;
      });
    }
  }

  Future<void> checkBellNotifications() async {
    try {
      final response = await ApiService.checkBellNotification(token: widget.token);
      
      if (response['success'] && response['data']['should_ring']) {
        final bellsToRing = response['data']['bells_to_ring'] as List;
        
        for (var bellJson in bellsToRing) {
          final bell = BellTiming.fromJson(bellJson);
          showBellNotification(bell);
        }
      }
    } catch (e) {
      // Silently handle notification check errors
      print('Bell notification check error: $e');
    }
  }

  void showBellNotification(BellTiming bell) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Row(
            children: [
              Text(bell.typeIcon, style: const TextStyle(fontSize: 24)),
              const SizedBox(width: 8),
              const Text('🔔 Bell Notification'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                bell.name,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text('Time: ${bell.formattedTime}'),
              Text('Type: ${bell.typeDescription}'),
              if (bell.description != null) ...[
                const SizedBox(height: 8),
                Text(bell.description!),
              ],
            ],
          ),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Bell Schedule'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: loadSchedule,
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error, size: 64, color: Colors.red),
                      const SizedBox(height: 16),
                      Text('Error: $error'),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: loadSchedule,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: loadSchedule,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Season and Next Bell Info
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(
                                      currentSeason == 'winter' 
                                          ? Icons.ac_unit 
                                          : Icons.wb_sunny,
                                      color: currentSeason == 'winter' 
                                          ? Colors.blue 
                                          : Colors.orange,
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      'Current Season: ${currentSeason.toUpperCase()}',
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                if (nextBell != null) ...[
                                  Row(
                                    children: [
                                      const Icon(Icons.schedule, color: Colors.green),
                                      const SizedBox(width: 8),
                                      Text(
                                        'Next Bell: ${nextBell!.name}',
                                        style: const TextStyle(fontSize: 16),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Time: ${nextBell!.formattedTime}',
                                    style: const TextStyle(
                                      fontSize: 14,
                                      color: Colors.grey,
                                    ),
                                  ),
                                ] else ...[
                                  const Row(
                                    children: [
                                      Icon(Icons.check_circle, color: Colors.green),
                                      SizedBox(width: 8),
                                      Text(
                                        'No more bells for today',
                                        style: TextStyle(fontSize: 16),
                                      ),
                                    ],
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        
                        // Schedule List
                        const Text(
                          'Today\'s Schedule',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        
                        if (schedule.isEmpty)
                          const Card(
                            child: Padding(
                              padding: EdgeInsets.all(16),
                              child: Text(
                                'No bell schedule available for today.',
                                style: TextStyle(fontSize: 16),
                              ),
                            ),
                          )
                        else
                          ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: schedule.length,
                            itemBuilder: (context, index) {
                              final bell = schedule[index];
                              final isNext = nextBell?.id == bell.id;
                              
                              return Card(
                                color: isNext ? Colors.green.shade50 : null,
                                child: ListTile(
                                  leading: CircleAvatar(
                                    backgroundColor: isNext 
                                        ? Colors.green 
                                        : Colors.blue.shade100,
                                    child: Text(
                                      bell.typeIcon,
                                      style: const TextStyle(fontSize: 20),
                                    ),
                                  ),
                                  title: Text(
                                    bell.name,
                                    style: TextStyle(
                                      fontWeight: isNext 
                                          ? FontWeight.bold 
                                          : FontWeight.normal,
                                    ),
                                  ),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('${bell.formattedTime} - ${bell.typeDescription}'),
                                      if (bell.description != null)
                                        Text(
                                          bell.description!,
                                          style: const TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey,
                                          ),
                                        ),
                                    ],
                                  ),
                                  trailing: isNext
                                      ? const Icon(
                                          Icons.schedule,
                                          color: Colors.green,
                                        )
                                      : null,
                                ),
                              );
                            },
                          ),
                      ],
                    ),
                  ),
                ),
    );
  }
}