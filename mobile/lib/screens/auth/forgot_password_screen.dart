import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../theme.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _emailController = TextEditingController();
  final _otpController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  int _step = 1; // 1: Email, 2: OTP, 3: New Password

  @override
  void dispose() {
    _emailController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _showNotification(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          message,
          style: GoogleFonts.urbanist(
            color: Colors.white,
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        backgroundColor: isError ? NoirTheme.error : NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      ),
    );
  }

  Future<void> _sendEmail() async {
    final email = _emailController.text.trim();
    if (email.isEmpty || !email.contains('@')) {
      _showNotification('Please enter a valid email address.', isError: true);
      return;
    }

    setState(() => _isLoading = true);
    try {
      await context.read<NoirApiClient>().forgotPassword(email);
      if (mounted) {
        _showNotification('Verification code sent to $email');
        setState(() => _step = 2);
      }
    } catch (e) {
      if (mounted) {
        _showNotification(
          e.toString().replaceAll("Exception: ", ""),
          isError: true,
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _verifyOtp() async {
    final otp = _otpController.text.trim();
    if (otp.length < 4) {
      _showNotification('Please enter the verification code sent to your email.', isError: true);
      return;
    }

    setState(() => _isLoading = true);
    try {
      await context.read<NoirApiClient>().verifyOtp(_emailController.text.trim(), otp);
      if (mounted) {
        _showNotification('Code verified successfully.');
        setState(() => _step = 3);
      }
    } catch (e) {
      if (mounted) {
        _showNotification(
          e.toString().replaceAll("Exception: ", ""),
          isError: true,
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resetPassword() async {
    final password = _passwordController.text;
    final confirmPassword = _confirmPasswordController.text;

    if (password.length < 8) {
      _showNotification('Password must be at least 8 characters long.', isError: true);
      return;
    }

    if (password != confirmPassword) {
      _showNotification('Passwords do not match.', isError: true);
      return;
    }

    setState(() => _isLoading = true);
    try {
      await context.read<NoirApiClient>().resetPassword(
            _emailController.text.trim(),
            _otpController.text.trim(),
            password,
          );
      if (mounted) {
        _showNotification('Password reset successful! Please sign in.');
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (mounted) {
        _showNotification(
          e.toString().replaceAll("Exception: ", ""),
          isError: true,
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDark ? Colors.white : NoirTheme.textPrimary;
    final subtitleColor = isDark ? const Color(0xFFA0A398) : NoirTheme.textSecondary;
    final inputBg = isDark ? const Color(0xFF282B24) : Colors.white;
    final borderColor = isDark ? const Color(0xFF383C32) : NoirTheme.divider;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF141512) : Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, size: 20, color: textColor),
          onPressed: () {
            if (_step > 1) {
              setState(() => _step--);
            } else {
              Navigator.of(context).pop();
            }
          },
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 8),

              // Brand Icon Badge
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: Image.asset(
                      'assets/images/noir_logo.jpg',
                      width: 48,
                      height: 48,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          color: NoirTheme.primary,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(Icons.lock_reset_rounded, color: Colors.white, size: 26),
                      ),
                    ),
                  ),

                  // Step Badge Indicator
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: NoirTheme.primary.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.2)),
                    ),
                    child: Text(
                      'STEP $_step OF 3',
                      style: GoogleFonts.urbanist(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: NoirTheme.primary,
                        letterSpacing: 1.1,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 28),

              // Step Header Text
              Text(
                _step == 1
                    ? 'Reset Password'
                    : _step == 2
                        ? 'Verify Code'
                        : 'New Password',
                style: GoogleFonts.urbanist(
                  fontSize: 32,
                  fontWeight: FontWeight.w700,
                  color: textColor,
                  height: 1.15,
                  letterSpacing: -0.5,
                ),
              ),

              const SizedBox(height: 10),

              Text(
                _step == 1
                    ? 'Enter your registered email address and we will send a 6-digit verification code.'
                    : _step == 2
                        ? 'We sent a verification code to ${_emailController.text.trim()}. Enter the code below.'
                        : 'Create a strong, new password for your Noir & Co. account.',
                style: GoogleFonts.urbanist(
                  fontSize: 15,
                  color: subtitleColor,
                  height: 1.45,
                ),
              ),

              const SizedBox(height: 32),

              // ── STEP 1: EMAIL INPUT ──
              if (_step == 1) ...[
                Text(
                  'EMAIL ADDRESS',
                  style: GoogleFonts.urbanist(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: subtitleColor,
                    letterSpacing: 1.0,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  decoration: BoxDecoration(
                    color: inputBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: borderColor),
                  ),
                  child: TextField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    style: GoogleFonts.urbanist(
                      color: textColor,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                    decoration: InputDecoration(
                      hintText: 'you@example.com',
                      hintStyle: GoogleFonts.urbanist(color: subtitleColor.withValues(alpha: 0.6)),
                      prefixIcon: Icon(Icons.mail_outline_rounded, color: NoirTheme.primary, size: 22),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    ),
                  ),
                ),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    onPressed: _isLoading ? null : _sendEmail,
                    child: _isLoading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(
                            'SEND VERIFICATION CODE',
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.8,
                            ),
                          ),
                  ),
                ),
              ]

              // ── STEP 2: OTP VERIFICATION ──
              else if (_step == 2) ...[
                Text(
                  '6-DIGIT CODE',
                  style: GoogleFonts.urbanist(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: subtitleColor,
                    letterSpacing: 1.0,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  decoration: BoxDecoration(
                    color: inputBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: borderColor),
                  ),
                  child: TextField(
                    controller: _otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.urbanist(
                      color: textColor,
                      fontSize: 26,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 10,
                    ),
                    decoration: InputDecoration(
                      counterText: '',
                      hintText: '••••••',
                      hintStyle: GoogleFonts.urbanist(color: subtitleColor.withValues(alpha: 0.4), letterSpacing: 8),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 18),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    onPressed: _isLoading ? null : _verifyOtp,
                    child: _isLoading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(
                            'VERIFY CODE',
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.8,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 16),
                Center(
                  child: TextButton.icon(
                    onPressed: _isLoading ? null : _sendEmail,
                    icon: Icon(Icons.refresh_rounded, size: 18, color: NoirTheme.primary),
                    label: Text(
                      'Resend Code',
                      style: GoogleFonts.urbanist(
                        color: NoirTheme.primary,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
                Center(
                  child: TextButton(
                    onPressed: () => setState(() => _step = 1),
                    child: Text(
                      'Change email address',
                      style: GoogleFonts.urbanist(
                        color: subtitleColor,
                        fontSize: 13,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ),
                ),
              ]

              // ── STEP 3: NEW PASSWORD ──
              else ...[
                Text(
                  'NEW PASSWORD',
                  style: GoogleFonts.urbanist(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: subtitleColor,
                    letterSpacing: 1.0,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  decoration: BoxDecoration(
                    color: inputBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: borderColor),
                  ),
                  child: TextField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    style: GoogleFonts.urbanist(
                      color: textColor,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                    decoration: InputDecoration(
                      hintText: 'At least 8 characters',
                      hintStyle: GoogleFonts.urbanist(color: subtitleColor.withValues(alpha: 0.6)),
                      prefixIcon: Icon(Icons.lock_outline_rounded, color: NoirTheme.primary, size: 22),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                          color: subtitleColor,
                          size: 20,
                        ),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  'CONFIRM PASSWORD',
                  style: GoogleFonts.urbanist(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: subtitleColor,
                    letterSpacing: 1.0,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  decoration: BoxDecoration(
                    color: inputBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: borderColor),
                  ),
                  child: TextField(
                    controller: _confirmPasswordController,
                    obscureText: _obscureConfirmPassword,
                    style: GoogleFonts.urbanist(
                      color: textColor,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                    decoration: InputDecoration(
                      hintText: 'Repeat new password',
                      hintStyle: GoogleFonts.urbanist(color: subtitleColor.withValues(alpha: 0.6)),
                      prefixIcon: Icon(Icons.lock_reset_rounded, color: NoirTheme.primary, size: 22),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscureConfirmPassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                          color: subtitleColor,
                          size: 20,
                        ),
                        onPressed: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword),
                      ),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    ),
                  ),
                ),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primaryDark,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    onPressed: _isLoading ? null : _resetPassword,
                    child: _isLoading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(
                            'UPDATE PASSWORD',
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.8,
                            ),
                          ),
                  ),
                ),
              ],

              const SizedBox(height: 36),

              // Back to Sign In Link
              Center(
                child: TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: Text(
                    '← Back to Sign In',
                    style: GoogleFonts.urbanist(
                      color: textColor,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}
