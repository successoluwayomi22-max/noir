import 'package:flutter/material.dart';
import 'package:local_auth/local_auth.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../providers/settings_provider.dart';
import '../theme.dart';
import 'package:google_fonts/google_fonts.dart';

/// Wraps the main layout and locks it behind biometrics:
///  1. On cold start (first frame) if biometric is enrolled
///  2. When returning from background after > 15 seconds away
class AppLockWrapper extends StatefulWidget {
  final Widget child;
  const AppLockWrapper({super.key, required this.child});

  @override
  State<AppLockWrapper> createState() => _AppLockWrapperState();
}

class _AppLockWrapperState extends State<AppLockWrapper>
    with WidgetsBindingObserver {
  final LocalAuthentication _localAuth = LocalAuthentication();

  bool _isLocked = false;
  bool _isAuthenticating = false;
  DateTime? _pausedAt;

  // Lock after 15 seconds in background
  static const _gracePeriodSeconds = 15;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    // Check on cold start — lock immediately if biometric enrolled
    WidgetsBinding.instance.addPostFrameCallback((_) => _checkAndLockOnStart());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  Future<void> _checkAndLockOnStart() async {
    if (!mounted) return;
    final auth = context.read<AuthProvider>();
    if (!auth.isLoggedIn) return;
    final enrolled = await auth.hasBiometricEnrolled();
    if (!enrolled || !mounted) return;
    setState(() => _isLocked = true);
    await _promptBiometric();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) async {
    if (!mounted) return;
    final auth = context.read<AuthProvider>();
    if (!auth.isLoggedIn) return;
    final enrolled = await auth.hasBiometricEnrolled();
    if (!enrolled) return;

    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden) {
      _pausedAt = DateTime.now();
    }

    if (state == AppLifecycleState.resumed && !_isLocked) {
      final elapsed = _pausedAt == null
          ? _gracePeriodSeconds + 1
          : DateTime.now().difference(_pausedAt!).inSeconds;

      if (elapsed >= _gracePeriodSeconds && mounted) {
        setState(() => _isLocked = true);
        await _promptBiometric();
      }
    }
  }

  Future<void> _promptBiometric() async {
    if (_isAuthenticating || !mounted) return;
    final brand = context.read<SettingsProvider>().brandName;
    setState(() => _isAuthenticating = true);

    try {
      final deviceSupported = await _localAuth.isDeviceSupported();
      final canCheck = await _localAuth.canCheckBiometrics;
      if (!deviceSupported || !canCheck) {
        if (mounted) {
          setState(() {
            _isLocked = true;
            _isAuthenticating = false;
          });
        }
        return;
      }

      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Verify your identity to continue with $brand.',
        persistAcrossBackgrounding: true,
        biometricOnly: false,
      );

      if (mounted) {
        setState(() {
          _isLocked = !authenticated;
          _isAuthenticating = false;
        });
      }
    } catch (e) {
      debugPrint('Biometric unlock cancelled or failed: $e');
      if (mounted) {
        setState(() {
          _isLocked = true; // STRICT: Keep locked on cancellation or error
          _isAuthenticating = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_isLocked) return widget.child;

    return Scaffold(
      backgroundColor: const Color(0xFF121412),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 90,
                height: 90,
                decoration: BoxDecoration(
                  color: NoirTheme.primary.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.lock_rounded, size: 42, color: NoirTheme.primary),
              ),
              const SizedBox(height: 28),
              Text(
                context.read<SettingsProvider>().brandName.toUpperCase(),
                style: GoogleFonts.cormorantGaramond(
                  fontSize: 28,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: 4,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'Your session is locked',
                style: GoogleFonts.urbanist(
                  fontSize: 16,
                  color: Colors.white54,
                  fontWeight: FontWeight.w400,
                ),
              ),
              const SizedBox(height: 50),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _isAuthenticating ? null : _promptBiometric,
                  icon: const Icon(Icons.fingerprint_rounded, size: 22, color: Colors.white),
                  label: Text(
                    _isAuthenticating ? 'Verifying...' : 'UNLOCK WITH BIOMETRICS',
                    style: GoogleFonts.urbanist(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: NoirTheme.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: () async {
                  final auth = context.read<AuthProvider>();
                  final nav = Navigator.of(context);
                  await auth.logout();
                  if (mounted) {
                    nav.pushNamedAndRemoveUntil('/login', (_) => false);
                  }
                },
                child: Text(
                  'Sign out instead',
                  style: GoogleFonts.urbanist(
                    fontSize: 14,
                    color: Colors.white38,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
