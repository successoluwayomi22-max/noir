import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:local_auth/local_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final LocalAuthentication _localAuth = LocalAuthentication();
  bool _obscurePassword = true;
  bool _canCheckBiometrics = false;
  bool _isEnrolled = false;

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
  }

  Future<void> _checkBiometrics() async {
    bool canCheck = false;
    bool enrolled = false;
    try {
      canCheck = await _localAuth.canCheckBiometrics || await _localAuth.isDeviceSupported();
      if (!mounted) return;
      final auth = context.read<AuthProvider>();
      enrolled = await auth.hasBiometricEnrolled();
    } catch (e) {
      debugPrint('Biometrics error: $e');
    }
    if (mounted) {
      setState(() {
        _canCheckBiometrics = canCheck;
        _isEnrolled = enrolled;
      });

      if (canCheck && enrolled) {
        final prefs = await SharedPreferences.getInstance();
        final autoPrompt = prefs.getBool('pref_biometric_auto_prompt') ?? true;
        if (autoPrompt) {
          Future.delayed(const Duration(milliseconds: 400), () {
            if (mounted) _authenticateBiometrics();
          });
        }
      }
    }
  }

  Future<void> _authenticateBiometrics() async {
    final auth = context.read<AuthProvider>();
    final brand = context.read<SettingsProvider>().brandName;
    final strings = AppStrings.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final nav = Navigator.of(context);
    
    final isEnrolled = await auth.hasBiometricEnrolled();
    if (!isEnrolled) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: const Text('Please log in with email & password once to enable quick biometric access on this device.'),
            backgroundColor: NoirTheme.primary,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      }
      return;
    }

    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: strings.biometricPrompt(brand),
        persistAcrossBackgrounding: true,
        biometricOnly: false,
      );
      if (!authenticated) {
        return;
      }
      if (mounted) {
        final success = await auth.loginWithBiometrics();
        if (success && mounted) {
          messenger.showSnackBar(
            SnackBar(
              content: Text('${strings.welcomeBack}, ${auth.user?.name ?? 'Member'}! ✨'),
              backgroundColor: NoirTheme.primary,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          );
          nav.pushReplacementNamed('/home');
        } else if (mounted) {
          messenger.showSnackBar(
            SnackBar(
              content: const Text('Biometric verification expired. Please sign in with password.'),
              backgroundColor: Colors.redAccent,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          );
        }
      }
    } catch (e) {
      debugPrint('Biometric login error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Biometric authentication failed: $e'),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final auth = context.read<AuthProvider>();
    auth.clearError();
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Please fill in all fields'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    final success = await auth.login(email, password);
    if (success && mounted) {
      Navigator.of(context).pushReplacementNamed('/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 48),

              // Brand Logo
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: Image.asset(
                  'assets/images/noir_logo.jpg',
                  width: 52,
                  height: 52,
                  fit: BoxFit.cover,
                  errorBuilder: (_, _, _) => Image.asset(
                    'assets/app_icon.jpg',
                    width: 52,
                    height: 52,
                    fit: BoxFit.cover,
                  ),
                ),
              ),
              const SizedBox(height: 24),

              Text(
                AppStrings.of(context).welcomeBack,
                style: GoogleFonts.urbanist(
                  fontSize: 34,
                  fontWeight: FontWeight.w800,
                  color: Theme.of(context).colorScheme.onSurface,
                  height: 1.15,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                AppStrings.of(context).signInToStore(context.read<SettingsProvider>().brandName),
                style: GoogleFonts.urbanist(
                  fontSize: 15,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                ),
              ),

              // Quick Biometrics Unlock Banner if enrolled
              if (_canCheckBiometrics && _isEnrolled) ...[
                const SizedBox(height: 24),
                InkWell(
                  onTap: _authenticateBiometrics,
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: NoirTheme.primary.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: NoirTheme.primary,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.fingerprint_rounded, size: 22, color: Colors.white),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Quick Biometric Unlock',
                                style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w800),
                              ),
                              Text(
                                'Tap to authenticate with Face ID or Fingerprint',
                                style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                              ),
                            ],
                          ),
                        ),
                        Icon(Icons.arrow_forward_ios_rounded, size: 14, color: NoirTheme.primary),
                      ],
                    ),
                  ),
                ),
              ],

              const SizedBox(height: 28),

              // Error
              if (auth.error != null)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  margin: const EdgeInsets.only(bottom: 20),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.error.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: Theme.of(context).colorScheme.error.withValues(alpha: 0.3),
                    ),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        Icons.error_outline_rounded,
                        size: 20,
                        color: Theme.of(context).colorScheme.error,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              auth.error!,
                              style: GoogleFonts.urbanist(
                                color: Theme.of(context).colorScheme.error,
                                fontSize: 13,
                                height: 1.35,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            if (auth.error!.contains('server') || auth.error!.contains('connection')) ...[
                              const SizedBox(height: 8),
                              InkWell(
                                onTap: () => Navigator.of(context).pushNamed('/offline'),
                                child: Text(
                                  'Diagnose Connection / Change IP →',
                                  style: GoogleFonts.urbanist(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: NoirTheme.primary,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

              // Email
              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                decoration: InputDecoration(
                  hintText: AppStrings.of(context).emailAddress,
                  prefixIcon: Icon(Icons.mail_outline_rounded, size: 20),
                ),
              ),
              SizedBox(height: 16),

              // Password
              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                textInputAction: TextInputAction.done,
                onSubmitted: (_) => _handleLogin(),
                decoration: InputDecoration(
                  hintText: AppStrings.of(context).password,
                  prefixIcon: Icon(Icons.lock_outline_rounded, size: 20),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscurePassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                      size: 20,
                    ),
                    onPressed: () =>
                        setState(() => _obscurePassword = !_obscurePassword),
                  ),
                ),
              ),
              SizedBox(height: 12),

              // Forgot password
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () => Navigator.of(context).pushNamed('/forgot-password'),
                  style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: Size.zero, tapTargetSize: MaterialTapTargetSize.shrinkWrap),
                  child: Text(
                    AppStrings.of(context).forgotPassword,
                    style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7), fontWeight: FontWeight.w600),
                  ),
                ),
              ),
              SizedBox(height: 20),

              // Login Button & Biometrics
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 56,
                      child: ElevatedButton(
                        onPressed: auth.isLoading ? null : _handleLogin,
                        child: auth.isLoading
                            ? SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : Text(
                                AppStrings.of(context).signIn,
                                style: GoogleFonts.urbanist(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  letterSpacing: 2.0,
                                ),
                              ),
                      ),
                    ),
                  ),
                  if (_canCheckBiometrics) ...[
                    SizedBox(width: 16),
                    SizedBox(
                      height: 56,
                      width: 56,
                      child: OutlinedButton(
                        onPressed: _authenticateBiometrics,
                        style: OutlinedButton.styleFrom(
                          padding: EdgeInsets.zero,
                          side: BorderSide(color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.2), width: 2),
                        ),
                        child: Icon(Icons.fingerprint_rounded, size: 28, color: Theme.of(context).colorScheme.primary),
                      ),
                    ),
                  ],
                ],
              ),
              SizedBox(height: 24),

              // Divider
              Row(
                children: [
                  Expanded(child: Divider()),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      'or',
                      style: GoogleFonts.urbanist(
                        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                        fontSize: 13,
                      ),
                    ),
                  ),
                  Expanded(child: Divider()),
                ],
              ),
              SizedBox(height: 24),

              // Google Login
              SizedBox(
                width: double.infinity,
                height: 56,
                child: OutlinedButton.icon(
                  onPressed: auth.isLoading
                      ? null
                      : () async {
                          final nav = Navigator.of(context);
                          final success = await auth.signInWithGoogle();
                          if (success && mounted) {
                            nav.pushReplacementNamed('/home');
                          }
                        },
                  icon: Icon(
                    Icons.g_mobiledata_rounded,
                    size: 32,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                  label: Text(
                    AppStrings.of(context).continueWithGoogle,
                    style: GoogleFonts.urbanist(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1.5,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ),
              ),
              SizedBox(height: 16),

              // Register Link
              SizedBox(
                width: double.infinity,
                height: 56,
                child: OutlinedButton(
                  onPressed: () => Navigator.of(context).pushNamed('/register'),
                  child: Text(
                    AppStrings.of(context).createAccount,
                    style: GoogleFonts.urbanist(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 2.0,
                    ),
                  ),
                ),
              ),
              SizedBox(height: 16),

              // Browse as Guest
              Center(
                child: TextButton(
                  onPressed: () =>
                      Navigator.of(context).pushReplacementNamed('/home'),
                  child: Text(
                    AppStrings.of(context).browseAsGuest,
                    style: GoogleFonts.urbanist(
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ),
              SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}
