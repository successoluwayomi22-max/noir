import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:local_auth/local_auth.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../providers/auth_provider.dart';
import '../../../providers/settings_provider.dart';
import '../../../theme.dart';
import '../../../l10n/app_strings.dart';

class PrivacySecurityScreen extends StatefulWidget {
  const PrivacySecurityScreen({super.key});

  @override
  State<PrivacySecurityScreen> createState() => _PrivacySecurityScreenState();
}

class _PrivacySecurityScreenState extends State<PrivacySecurityScreen> {
  final LocalAuthentication _localAuth = LocalAuthentication();
  final FlutterSecureStorage _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  bool _autoPromptOnLaunch = true;
  bool _analytics = false;
  bool _emails = true;
  bool _isEnrolled = false;
  List<BiometricType> _availableBiometrics = [];
  bool _loadingBiometrics = true;

  @override
  void initState() {
    super.initState();
    _loadPrefsAndBiometrics();
  }

  Future<void> _loadPrefsAndBiometrics() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (!mounted) return;
      final auth = context.read<AuthProvider>();
      final enrolled = await auth.hasBiometricEnrolled();

      bool supported = false;
      List<BiometricType> types = [];
      try {
        supported = await _localAuth.canCheckBiometrics || await _localAuth.isDeviceSupported();
        if (supported) {
          types = await _localAuth.getAvailableBiometrics();
        }
      } catch (e) {
        debugPrint('Biometric check error: $e');
      }

      if (mounted) {
        setState(() {
          _autoPromptOnLaunch = prefs.getBool('pref_biometric_auto_prompt') ?? true;
          _analytics = prefs.getBool('pref_analytics') ?? false;
          _emails = prefs.getBool('pref_emails') ?? true;
          _isEnrolled = enrolled;
          _availableBiometrics = types;
          _loadingBiometrics = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _loadingBiometrics = false;
        });
      }
    }
  }

  Future<void> _savePref(String key, bool val) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(key, val);
  }

  Future<void> _registerBiometric() async {
    final brandName = context.read<SettingsProvider>().brandName;
    final auth = context.read<AuthProvider>();
    final user = auth.user;

    // Only block if the device has absolutely no biometric hardware at all
    final deviceSupported = await _localAuth.isDeviceSupported();
    if (!deviceSupported) {
      _showSnack('This device does not support biometric authentication.', isError: true);
      return;
    }

    try {
      // Prompt biometric sensor — supports Face AND Fingerprint (device decides)
      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Look at the camera or place your finger to register biometric login for $brandName.',
        persistAcrossBackgrounding: true,
        biometricOnly: false,
      );

      if (!authenticated) {
        if (mounted) _showSnack('Biometric scan was cancelled. Try again.', isError: true);
        return;
      }

      if (!mounted) return;

      // Step 2: Read the CURRENT active session token and save it as the biometric token
      final currentToken = await _secureStorage.read(key: 'auth_token_secure');
      if (currentToken == null || currentToken.isEmpty) {
        _showSnack('You must be logged in to enroll biometrics. Please log in first.', isError: true);
        return;
      }

      // Step 3: Write the token as biometric_auth_token_secure
      await _secureStorage.write(key: 'biometric_auth_token_secure', value: currentToken);

      // Step 4: Save enrolled user ID (banking rule: biometric is per-account)
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('biometric_enrolled_user_id', user?.id.toString() ?? '');
      await prefs.setBool('pref_biometrics', true);
      await prefs.setBool('pref_biometric_auto_prompt', true);

      if (mounted) {
        setState(() {
          _isEnrolled = true;
          _autoPromptOnLaunch = true;
        });

        _showSuccessDialog(user?.name ?? 'Member');
      }
    } catch (e) {
      debugPrint('Biometric registration error: $e');
      if (mounted) {
        _showSnack('Biometric registration error: ${e.toString().replaceAll('Exception: ', '')}', isError: true);
      }
    }
  }

  Future<void> _testBiometricUnlock() async {
    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Testing biometric unlock on this device...',
      );

      if (authenticated && mounted) {
        _showSnack('Biometric verification passed successfully! ✨', isError: false);
      } else if (mounted) {
        _showSnack('Biometric verification was cancelled.', isError: true);
      }
    } catch (e) {
      if (mounted) _showSnack('Verification failed: ${e.toString().replaceAll('Exception: ', '')}', isError: true);
    }
  }

  Future<void> _removeBiometric() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Theme.of(ctx).colorScheme.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('Remove Biometrics?', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        content: Text(
          'You will need to sign in with your email and password to access your account.',
          style: GoogleFonts.urbanist(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('Cancel', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Remove', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      // Use provider method to properly clear all enrollment data
      final auth = context.read<AuthProvider>();
      await auth.clearBiometricEnrollment();

      setState(() {
        _isEnrolled = false;
        _autoPromptOnLaunch = false;
      });

      _showSnack('Biometrics removed. Re-enroll to use biometric login again.');
    }
  }

  void _showSuccessDialog(String name) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Theme.of(ctx).colorScheme.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        content: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: NoirTheme.primary.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.fingerprint_rounded, size: 40, color: NoirTheme.primary),
              ),
              const SizedBox(height: 20),
              Text(
                'Biometrics Registered!',
                textAlign: TextAlign.center,
                style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 10),
              Text(
                'Face ID and Fingerprint are now active for $name. You can now use instant biometric unlock when launching the app.',
                textAlign: TextAlign.center,
                style: GoogleFonts.urbanist(fontSize: 14, color: Theme.of(ctx).colorScheme.onSurface.withValues(alpha: 0.7)),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: NoirTheme.primary,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => Navigator.pop(ctx),
                  child: Text('DONE', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, color: Colors.white)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showSnack(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message, style: GoogleFonts.urbanist(fontWeight: FontWeight.w600)),
        backgroundColor: isError ? Colors.redAccent : NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  String _getBiometricTypesString() {
    if (_availableBiometrics.isEmpty) {
      return 'Face ID / Fingerprint / Device Biometrics';
    }
    final names = _availableBiometrics.map((t) {
      switch (t) {
        case BiometricType.face:
          return 'Face ID';
        case BiometricType.fingerprint:
          return 'Fingerprint Scanner';
        case BiometricType.iris:
          return 'Iris Scanner';
        default:
          return 'Device Biometrics';
      }
    }).toSet().toList();
    return names.join(', ');
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: theme.scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(AppStrings.of(context).privacySecurity, style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
      ),
      body: _loadingBiometrics
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              children: [
                // Biometrics Registration Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: _isEnrolled
                          ? NoirTheme.primary.withValues(alpha: 0.5)
                          : theme.colorScheme.outline.withValues(alpha: 0.3),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: (_isEnrolled ? NoirTheme.primary : Colors.grey).withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Icon(
                              Icons.fingerprint_rounded,
                              size: 28,
                              color: _isEnrolled ? NoirTheme.primary : Colors.grey,
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Text(
                                      'Face ID & Biometrics',
                                      style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w800),
                                    ),
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: (_isEnrolled ? NoirTheme.primary : Colors.grey).withValues(alpha: 0.15),
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Text(
                                        _isEnrolled ? 'ACTIVE' : 'NOT ENROLLED',
                                        style: GoogleFonts.urbanist(
                                          fontSize: 10,
                                          fontWeight: FontWeight.w800,
                                          color: _isEnrolled ? NoirTheme.primary : Colors.grey,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _isEnrolled
                                      ? 'Enrolled for ${user?.name ?? 'Member'}'
                                      : 'Quickly unlock the app using your face or fingerprint',
                                  style: GoogleFonts.urbanist(
                                    fontSize: 13,
                                    color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: theme.scaffoldBackgroundColor,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.devices_rounded, size: 16, color: theme.colorScheme.primary),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Hardware: ${_getBiometricTypesString()}',
                                style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: _registerBiometric,
                              icon: const Icon(Icons.qr_code_scanner_rounded, size: 18, color: Colors.white),
                              label: Text(
                                _isEnrolled ? 'RE-REGISTER' : 'REGISTER BIOMETRIC',
                                style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w700, color: Colors.white),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: NoirTheme.primary,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                            ),
                          ),
                          if (_isEnrolled) ...[
                            const SizedBox(width: 10),
                            IconButton.outlined(
                              onPressed: _testBiometricUnlock,
                              icon: const Icon(Icons.check_circle_outline_rounded, size: 20),
                              tooltip: 'Test verification',
                              style: OutlinedButton.styleFrom(
                                side: BorderSide(color: NoirTheme.primary),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                            ),
                            const SizedBox(width: 6),
                            IconButton.outlined(
                              onPressed: _removeBiometric,
                              icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
                              tooltip: 'Remove',
                              style: OutlinedButton.styleFrom(
                                side: BorderSide(color: Colors.redAccent.withValues(alpha: 0.4)),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),
                Text('PREFERENCES', style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w800, color: NoirTheme.primary, letterSpacing: 1.5)),
                const SizedBox(height: 10),

                // Auto Prompt Toggle
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text('Auto-Prompt on App Launch', style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w600)),
                  subtitle: Text('Immediately scan biometric on opening Noir', style: GoogleFonts.urbanist(fontSize: 13, color: theme.colorScheme.onSurface.withValues(alpha: 0.6))),
                  activeThumbColor: NoirTheme.primary,
                  value: _autoPromptOnLaunch && _isEnrolled,
                  onChanged: _isEnrolled
                      ? (val) {
                          setState(() => _autoPromptOnLaunch = val);
                          _savePref('pref_biometric_auto_prompt', val);
                        }
                      : null,
                ),
                Divider(height: 1, color: theme.colorScheme.outline.withValues(alpha: 0.2)),

                // Analytics
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text('Anonymous App Telemetry', style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w600)),
                  subtitle: Text('Help improve app stability without collecting personal records', style: GoogleFonts.urbanist(fontSize: 13, color: theme.colorScheme.onSurface.withValues(alpha: 0.6))),
                  activeThumbColor: NoirTheme.primary,
                  value: _analytics,
                  onChanged: (val) {
                    setState(() => _analytics = val);
                    _savePref('pref_analytics', val);
                  },
                ),
                Divider(height: 1, color: theme.colorScheme.outline.withValues(alpha: 0.2)),

                // Marketing Emails
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text('Concierge Communications', style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w600)),
                  subtitle: Text('Receive private collection announcements and event invites', style: GoogleFonts.urbanist(fontSize: 13, color: theme.colorScheme.onSurface.withValues(alpha: 0.6))),
                  activeThumbColor: NoirTheme.primary,
                  value: _emails,
                  onChanged: (val) {
                    setState(() => _emails = val);
                    _savePref('pref_emails', val);
                  },
                ),

                const SizedBox(height: 32),
                Text('ACCOUNT CREDENTIALS', style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w800, color: NoirTheme.primary, letterSpacing: 1.5)),
                const SizedBox(height: 12),

                OutlinedButton.icon(
                  onPressed: () => _showChangePasswordDialog(context),
                  icon: const Icon(Icons.lock_reset_rounded, size: 18),
                  label: Text('CHANGE ACCOUNT PASSWORD', style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w700)),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    side: BorderSide(color: NoirTheme.primary),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 40),
              ],
            ),
    );
  }

  void _showChangePasswordDialog(BuildContext context) {
    final formKey = GlobalKey<FormState>();
    final currentCtrl = TextEditingController();
    final newCtrl = TextEditingController();
    final confirmCtrl = TextEditingController();
    final otpCtrl = TextEditingController();

    bool obscureCurrent = true;
    bool obscureNew = true;
    bool obscureConfirm = true;
    bool isOtpMode = false;
    bool isSubmitting = false;
    bool isSendingOtp = false;
    String? statusMessage;
    String? errorMessage;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          final api = context.read<AuthProvider>().api;

          Future<void> sendOtp() async {
            setModalState(() {
              isSendingOtp = true;
              errorMessage = null;
              statusMessage = null;
            });
            try {
              final res = await api.requestPasswordOtp();
              setModalState(() {
                isSendingOtp = false;
                statusMessage = res['message'] ?? 'OTP code sent to your registered email.';
              });
            } catch (e) {
              setModalState(() {
                isSendingOtp = false;
                errorMessage = e.toString().replaceAll('Exception: ', '');
              });
            }
          }

          Future<void> submit() async {
            if (!formKey.currentState!.validate()) return;
            setModalState(() {
              isSubmitting = true;
              errorMessage = null;
              statusMessage = null;
            });

            try {
              if (isOtpMode) {
                await api.changePasswordWithOtp(
                  otp: otpCtrl.text.trim(),
                  newPassword: newCtrl.text,
                );
              } else {
                await api.changePassword(
                  currentPassword: currentCtrl.text,
                  newPassword: newCtrl.text,
                );
              }
              if (ctx.mounted) {
                Navigator.pop(ctx);
                if (mounted) _showSnack('Password updated successfully.');
              }
            } catch (e) {
              setModalState(() {
                isSubmitting = false;
                errorMessage = e.toString().replaceAll('Exception: ', '');
              });
            }
          }

          return Padding(
            padding: EdgeInsets.only(left: 24, right: 24, top: 24, bottom: MediaQuery.of(ctx).viewInsets.bottom + 24),
            child: Form(
              key: formKey,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Change Password', style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.w800)),
                        TextButton(
                          onPressed: () {
                            setModalState(() {
                              isOtpMode = !isOtpMode;
                              errorMessage = null;
                              statusMessage = null;
                            });
                          },
                          child: Text(
                            isOtpMode ? 'Use Current Password' : 'Use Email OTP',
                            style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w700, color: NoirTheme.primary),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    if (errorMessage != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.red.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
                        ),
                        child: Text(
                          errorMessage!,
                          style: GoogleFonts.urbanist(color: Colors.red, fontSize: 13, fontWeight: FontWeight.w600),
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],

                    if (statusMessage != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.green.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                        ),
                        child: Text(
                          statusMessage!,
                          style: GoogleFonts.urbanist(color: Colors.green.shade800, fontSize: 13, fontWeight: FontWeight.w600),
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],

                    if (!isOtpMode) ...[
                      TextFormField(
                        controller: currentCtrl,
                        obscureText: obscureCurrent,
                        decoration: _inputDecor('Current password').copyWith(
                          suffixIcon: IconButton(
                            icon: Icon(obscureCurrent ? Icons.visibility_off : Icons.visibility, color: Theme.of(context).colorScheme.outline),
                            onPressed: () => setModalState(() => obscureCurrent = !obscureCurrent),
                          ),
                        ),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                      const SizedBox(height: 12),
                    ] else ...[
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: otpCtrl,
                              keyboardType: TextInputType.number,
                              maxLength: 6,
                              decoration: _inputDecor('6-Digit OTP').copyWith(
                                hintText: '000000',
                                counterText: '',
                              ),
                              validator: (v) => (v == null || v.trim().length != 6) ? 'Enter 6-digit OTP' : null,
                            ),
                          ),
                          const SizedBox(width: 8),
                          SizedBox(
                            height: 48,
                            child: OutlinedButton(
                              onPressed: isSendingOtp ? null : sendOtp,
                              style: OutlinedButton.styleFrom(
                                side: BorderSide(color: NoirTheme.primary),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              child: isSendingOtp
                                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                                  : Text('Send OTP', style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w700, color: NoirTheme.primary)),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                    ],

                    TextFormField(
                      controller: newCtrl,
                      obscureText: obscureNew,
                      decoration: _inputDecor('New password').copyWith(
                        suffixIcon: IconButton(
                          icon: Icon(obscureNew ? Icons.visibility_off : Icons.visibility, color: Theme.of(context).colorScheme.outline),
                          onPressed: () => setModalState(() => obscureNew = !obscureNew),
                        ),
                      ),
                      validator: (v) {
                        if (v == null || v.length < 8) return 'At least 8 characters';
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: confirmCtrl,
                      obscureText: obscureConfirm,
                      decoration: _inputDecor('Confirm new password').copyWith(
                        suffixIcon: IconButton(
                          icon: Icon(obscureConfirm ? Icons.visibility_off : Icons.visibility, color: Theme.of(context).colorScheme.outline),
                          onPressed: () => setModalState(() => obscureConfirm = !obscureConfirm),
                        ),
                      ),
                      validator: (v) => v != newCtrl.text ? 'Passwords do not match' : null,
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: NoirTheme.primary,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: isSubmitting ? null : submit,
                      child: isSubmitting
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : Text('UPDATE PASSWORD', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, color: Colors.white)),
                    ),
                  ],
                ),
              ),
            ),
          );
        }
      ),
    );
  }

  InputDecoration _inputDecor(String label) => InputDecoration(
        labelText: label,
        filled: true,
        fillColor: Theme.of(context).scaffoldBackgroundColor,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Theme.of(context).colorScheme.outline)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Theme.of(context).colorScheme.outline)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Theme.of(context).colorScheme.primary)),
      );
}
