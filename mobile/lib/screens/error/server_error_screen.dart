import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../theme.dart';

class ServerErrorScreen extends StatefulWidget {
  final String? errorMessage;
  final VoidCallback? onRetry;

  const ServerErrorScreen({
    super.key,
    this.errorMessage,
    this.onRetry,
  });

  @override
  State<ServerErrorScreen> createState() => _ServerErrorScreenState();
}

class _ServerErrorScreenState extends State<ServerErrorScreen> {
  bool _retrying = false;

  Future<void> _handleRetry() async {
    if (widget.onRetry != null) {
      setState(() => _retrying = true);
      await Future.delayed(const Duration(milliseconds: 600));
      widget.onRetry!();
      if (mounted) setState(() => _retrying = false);
    } else {
      Navigator.of(context).pushReplacementNamed('/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.of(context).maybePop(),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Error Halo Badge
                Container(
                  width: 104,
                  height: 104,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: theme.colorScheme.error.withValues(alpha: 0.1),
                    border: Border.all(
                      color: theme.colorScheme.error.withValues(alpha: 0.3),
                      width: 2,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: theme.colorScheme.error.withValues(alpha: 0.15),
                        blurRadius: 36,
                        spreadRadius: 6,
                      ),
                    ],
                  ),
                  child: Center(
                    child: Icon(
                      Icons.cloud_off_rounded,
                      size: 46,
                      color: theme.colorScheme.error,
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                Text(
                  'Atelier Disruption',
                  style: GoogleFonts.urbanist(
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                    color: theme.colorScheme.onSurface,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),

                Text(
                  widget.errorMessage ??
                      'The Noir boutique services are temporarily unable to process this request. Our engineering team has been notified.',
                  style: GoogleFonts.urbanist(
                    fontSize: 14,
                    height: 1.5,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 36),

                // Retry Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _retrying ? null : _handleRetry,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: 0,
                    ),
                    child: _retrying
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            'Refresh Connection',
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.5,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 16),

                TextButton(
                  onPressed: () => Navigator.of(context).pushReplacementNamed('/home'),
                  child: Text(
                    'Return to Boutique Home',
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
