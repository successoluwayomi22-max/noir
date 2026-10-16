import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../api_client.dart';
import '../../theme.dart';

class OfflineScreen extends StatefulWidget {
  final VoidCallback? onRetrySuccess;

  const OfflineScreen({super.key, this.onRetrySuccess});

  @override
  State<OfflineScreen> createState() => _OfflineScreenState();
}

class _OfflineScreenState extends State<OfflineScreen> {
  bool _isChecking = false;
  String? _statusMessage;
  bool _isHealthy = false;

  @override
  void initState() {
    super.initState();
    _checkConnection();
  }

  Future<void> _checkConnection() async {
    setState(() {
      _isChecking = true;
      _statusMessage = 'Probing Noir server connection...';
    });

    final api = context.read<NoirApiClient>();
    final targetUrl = '${api.baseUrl}/api/v1/health';

    try {
      final stopwatch = Stopwatch()..start();
      final res = await http.get(Uri.parse(targetUrl)).timeout(const Duration(seconds: 4));
      stopwatch.stop();

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'healthy' || data['status'] == 'ok') {
          setState(() {
            _isChecking = false;
            _isHealthy = true;
            _statusMessage = 'Connected! Latency: ${stopwatch.elapsedMilliseconds}ms';
          });

          if (mounted && widget.onRetrySuccess != null) {
            widget.onRetrySuccess!();
          } else if (mounted) {
            Navigator.of(context).pushReplacementNamed('/home');
          }
          return;
        }
      }

      setState(() {
        _isChecking = false;
        _isHealthy = false;
        _statusMessage = 'Server responded with status code ${res.statusCode}.';
      });
    } on SocketException catch (_) {
      setState(() {
        _isChecking = false;
        _isHealthy = false;
        _statusMessage = 'Could not reach $targetUrl. Please ensure device is on the same Wi-Fi and server is active.';
      });
    } catch (e) {
      setState(() {
        _isChecking = false;
        _isHealthy = false;
        _statusMessage = 'Connection failed: ${e.toString().replaceAll('Exception: ', '')}';
      });
    }
  }

  void _showConfigureServerDialog() {
    final api = context.read<NoirApiClient>();
    final controller = TextEditingController(text: api.baseUrl);

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Theme.of(context).cardColor,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'Configure Server URL',
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Enter the backend URL or reset to the official production server:',
              style: GoogleFonts.urbanist(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: InputDecoration(
                hintText: 'https://noir-73fy.onrender.com',
                hintStyle: GoogleFonts.urbanist(fontSize: 12),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              style: GoogleFonts.urbanist(fontSize: 13),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () {
                controller.text = 'https://noir-73fy.onrender.com';
              },
              icon: const Icon(Icons.cloud_done_rounded, size: 16),
              label: Text(
                'Use Render Production Server',
                style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Cancel', style: GoogleFonts.urbanist()),
          ),
          ElevatedButton(
            onPressed: () async {
              final newUrl = controller.text.trim();
              if (newUrl.isNotEmpty) {
                final navigator = Navigator.of(ctx);
                final messenger = ScaffoldMessenger.of(context);
                final prefs = await SharedPreferences.getInstance();
                await prefs.setString('custom_api_base_url', newUrl);
                api.setBaseUrl(newUrl);
                if (mounted) {
                  navigator.pop();
                  messenger.showSnackBar(
                    SnackBar(content: Text('Server URL set to: $newUrl')),
                  );
                  _checkConnection();
                }
              }
            },
            child: Text('Save & Test', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final api = context.watch<NoirApiClient>();

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Top Brand Mark
                ClipRRect(
                  borderRadius: BorderRadius.circular(14),
                  child: Image.asset(
                    'assets/images/noir_logo.jpg',
                    width: 48,
                    height: 48,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => Image.asset(
                      'assets/app_icon.jpg',
                      width: 48,
                      height: 48,
                      fit: BoxFit.cover,
                    ),
                  ),
                ),
                const SizedBox(height: 36),

                // Animated glowing offline badge
                Container(
                  width: 110,
                  height: 110,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: const Color(0xFFD4A373).withValues(alpha: 0.1),
                    border: Border.all(
                      color: const Color(0xFFD4A373).withValues(alpha: 0.3),
                      width: 2,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFD4A373).withValues(alpha: 0.15),
                        blurRadius: 40,
                        spreadRadius: 8,
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Icon(
                      Icons.wifi_off_rounded,
                      size: 48,
                      color: Color(0xFFD4A373),
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                // Title
                Text(
                  'Connection Required',
                  style: GoogleFonts.urbanist(
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                    color: theme.colorScheme.onSurface,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),

                // Description
                Text(
                  'Noir & Co. requires an active connection to synchronize curated drops, real-time prices, and your VIP bag.',
                  style: GoogleFonts.urbanist(
                    fontSize: 14,
                    height: 1.5,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 28),

                // Diagnostics Card
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF161816) : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: isDark ? Colors.white10 : Colors.black12,
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(
                            _isHealthy ? Icons.check_circle_rounded : Icons.info_outline_rounded,
                            size: 16,
                            color: _isHealthy ? Colors.greenAccent : const Color(0xFFD4A373),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'SERVER TARGET',
                            style: GoogleFonts.urbanist(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 1.2,
                              color: theme.colorScheme.onSurface.withValues(alpha: 0.5),
                            ),
                          ),
                          const Spacer(),
                          InkWell(
                            onTap: _showConfigureServerDialog,
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                              child: Text(
                                'Change',
                                style: GoogleFonts.urbanist(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: NoirTheme.primary,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        api.baseUrl,
                        style: GoogleFonts.firaCode(
                          fontSize: 12,
                          color: theme.colorScheme.onSurface.withValues(alpha: 0.85),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (_statusMessage != null) ...[
                        const Divider(height: 16),
                        Text(
                          _statusMessage!,
                          style: GoogleFonts.urbanist(
                            fontSize: 12,
                            color: _isHealthy
                                ? Colors.greenAccent
                                : theme.colorScheme.onSurface.withValues(alpha: 0.6),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 32),

                // Retry Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _isChecking ? null : _checkConnection,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: 0,
                    ),
                    child: _isChecking
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            'Retry Connection',
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.5,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 16),

                // Secondary browse/offline button
                TextButton(
                  onPressed: () {
                    Navigator.of(context).pushReplacementNamed('/home');
                  },
                  child: Text(
                    'Continue in Offline Mode',
                    style: GoogleFonts.urbanist(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
