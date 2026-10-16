import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../api_client.dart';
import '../models/app_user.dart';
import 'package:google_sign_in/google_sign_in.dart';

class AuthProvider extends ChangeNotifier {
  final NoirApiClient api;
  final FlutterSecureStorage _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  bool _isLoading = false;
  bool _isLoggedIn = false;
  AppUser? _user;
  String? _error;

  AuthProvider({required this.api});

  bool get isLoading => _isLoading;
  bool get isLoggedIn => _isLoggedIn;
  bool get isAuthenticated => _isLoggedIn;
  bool get isGuest => _isLoggedIn == false && _user == null;
  AppUser? get user => _user;
  String? get error => _error;

  Future<void> init() async {
    final token = await _secureStorage.read(key: 'auth_token_secure');
    if (token != null && token.isNotEmpty) {
      api.setToken(token);

      // Fast load cached profile instantly (0ms UI latency)
      final cached = await _loadCachedUser();
      if (cached != null) {
        _user = cached;
        _isLoggedIn = true;
        notifyListeners();
      }

      // Background fresh fetch
      _fetchFreshProfile();
    } else {
      _isLoggedIn = false;
      _user = null;
      notifyListeners();
    }
  }

  Future<void> _fetchFreshProfile() async {
    _isLoading = true;
    try {
      final data = await api.getMe();
      final rawUser = data['user'];
      if (rawUser is Map<String, dynamic>) {
        _user = AppUser.fromJson(rawUser);
        _isLoggedIn = true;
        await _saveCachedUser(_user!);
      } else {
        await _clearSession();
      }
    } catch (e) {
      final msg = e.toString();
      if (msg.contains('Unauthorized') || msg.contains('401') || msg.contains('Unauthenticated') || msg.contains('Failed to get profile')) {
        await _clearSession();
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  String _sanitizeErrorMessage(dynamic e) {
    final str = e.toString();
    if (str.contains('ApiException: 10') || str.contains('sign_in_failed') || str.contains('DEVELOPER_ERROR')) {
      return 'Google Sign-In is not configured for this app build (Firebase SHA-1 fingerprint required). Please sign in with email and password.';
    }
    if (str.contains('ApiException: 12500')) {
      return 'Google Sign-In service unavailable. Please check your Google Play Services or use email sign in.';
    }
    if (str.contains('SocketException') ||
        str.contains('ClientException') ||
        str.contains('Failed host lookup') ||
        str.contains('Connection refused') ||
        str.contains('Connection timed out') ||
        str.contains('HandshakeException')) {
      return 'Unable to reach the Noir server. Please check your Wi-Fi/cellular connection and ensure the server is active.';
    }
    if (str.contains('already registered') || str.contains('already exists')) {
      return 'An account with this email already exists. Please sign in instead.';
    }
    if (str.contains('Password must be at least 8 characters')) {
      return 'Password must be at least 8 characters and contain both letters and numbers.';
    }
    if (str.contains('Invalid email') || str.contains('Invalid email/username or password')) {
      return 'Invalid email or password. Please verify your credentials and try again.';
    }

    var clean = str
        .replaceAll(RegExp(r'^Exception:\s*'), '')
        .replaceAll(RegExp(r'^PlatformException\([^,]+,\s*'), '')
        .replaceAll(RegExp(r',\s*null,\s*null\)$'), '')
        .trim();
    if (clean.isEmpty) {
      return 'An unexpected error occurred. Please try again.';
    }
    return clean;
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await api.login(email, password);
      final token = data['token'] as String;
      await _saveToken(token);
      api.setToken(token);
      _isLoggedIn = true;
      try {
        final me = await api.getMe();
        final rawUser = me['user'];
        if (rawUser is Map<String, dynamic>) {
          _user = AppUser.fromJson(rawUser);
          await _saveCachedUser(_user!);
          // ── Banking rule: if different account logs in, reset biometric ──
          await _checkAndClearStaleEnrollment(_user!.id.toString());
        }
      } catch (_) {}
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = _sanitizeErrorMessage(e);
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> signInWithGoogle() async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final googleSignIn = GoogleSignIn(
        scopes: ['email', 'profile'],
      );
      final googleUser = await googleSignIn.signIn();
      if (googleUser == null) {
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      final googleAuth = await googleUser.authentication;
      final idToken = googleAuth.idToken;
      
      if (idToken == null) {
        throw Exception('Failed to obtain ID token from Google.');
      }
      
      final data = await api.loginWithGoogle(idToken);
      final token = data['token'] as String;
      await _saveToken(token);
      api.setToken(token);
      _isLoggedIn = true;
      try {
        final me = await api.getMe();
        final rawUser = me['user'];
        if (rawUser is Map<String, dynamic>) {
          _user = AppUser.fromJson(rawUser);
          // ── Banking rule: if different account, reset biometric ──
          await _checkAndClearStaleEnrollment(_user!.id.toString());
        }
      } catch (_) {}
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = _sanitizeErrorMessage(e);
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }


  Future<bool> register(String name, String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await api.register(name, email, password);
      final token = data['token'] as String;
      await _saveToken(token);
      api.setToken(token);
      _isLoggedIn = true;
      try {
        final me = await api.getMe();
        final rawUser = me['user'];
        if (rawUser is Map<String, dynamic>) {
          _user = AppUser.fromJson(rawUser);
          // ── Banking rule: new account always resets biometric ──
          await _checkAndClearStaleEnrollment(_user!.id.toString());
        }
      } catch (_) {}
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = _sanitizeErrorMessage(e);
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    // ── Banking rule: clear biometric on logout ──────────────────────────────
    await _clearBiometricEnrollment();
    await _clearSession();
    notifyListeners();
  }

  Future<void> refreshProfile() async {
    if (!_isLoggedIn || api.token == null) return;
    if (_user == null) {
      _isLoading = true;
      notifyListeners();
    }
    try {
      final data = await api.getMe();
      final rawUser = data['user'];
      if (rawUser is Map<String, dynamic>) {
        _user = AppUser.fromJson(rawUser);
        await _saveCachedUser(_user!);
      }
    } catch (_) {}
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateProfile({required String name, String? phone}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final res = await api.updateProfile(name: name, phone: phone);
      final rawUser = res['user'];
      if (rawUser is Map<String, dynamic>) {
        _user = AppUser.fromJson(rawUser);
      } else if (_user != null) {
        _user = _user!.copyWith(name: name, phone: phone);
      }
      if (_user != null) {
        await _saveCachedUser(_user!);
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> uploadAvatar(String base64Image) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final newUrl = await api.uploadProfileImage(base64Image);
      if (_user != null && newUrl.isNotEmpty) {
        _user = _user!.copyWith(avatar: newUrl);
        await _saveCachedUser(_user!);
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> removeAvatar() async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      await api.deleteProfileImage();
      if (_user != null) {
        _user = _user!.copyWith(avatar: '');
        await _saveCachedUser(_user!);
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // ─── Biometric API ───────────────────────────────────────────────────────────

  /// Returns true if a biometric token is stored for this device.
  /// Account-switch detection is handled at login time via _checkAndClearStaleEnrollment.
  Future<bool> hasBiometricEnrolled() async {
    try {
      final token = await _secureStorage.read(key: 'biometric_auth_token_secure');
      return token != null && token.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  /// Called by biometric login: reads the encrypted biometric token from secure
  /// storage, sets it on the API client, then marks the user as logged in.
  Future<bool> loginWithBiometrics() async {
    final token = await _secureStorage.read(key: 'biometric_auth_token_secure');
    if (token == null || token.isEmpty) return false;

    api.setToken(token);
    await _secureStorage.write(key: 'auth_token_secure', value: token);

    final cached = await _loadCachedUser();
    if (cached != null) {
      _user = cached;
      _isLoggedIn = true;
      notifyListeners();
    }

    try {
      final me = await api.getMe();
      final rawUser = me['user'];
      if (rawUser is Map<String, dynamic>) {
        _user = AppUser.fromJson(rawUser);
        await _saveCachedUser(_user!);
      }
      _isLoggedIn = true;
      notifyListeners();
      return true;
    } catch (e) {
      if (cached != null) {
        _isLoggedIn = true;
        notifyListeners();
        return true;
      }
      // Token invalid — clear everything including biometric
      await _clearBiometricEnrollment();
      await _clearSession();
      notifyListeners();
      return false;
    }
  }

  /// Clears biometric enrollment for the current account (call from privacy screen).
  Future<void> clearBiometricEnrollment() async {
    await _clearBiometricEnrollment();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }

  // ─── Private helpers ───────────────────────────────────────────────────────

  /// Saves session token only — does NOT auto-enroll biometrics.
  /// Biometric enrollment is explicit (user must register in Settings).
  Future<void> _saveToken(String token) async {
    await _secureStorage.write(key: 'auth_token_secure', value: token);
  }

  /// Checks if the newly logged-in user is DIFFERENT from the previously
  /// enrolled biometric user. If so, wipes the enrollment (banking rule).
  Future<void> _checkAndClearStaleEnrollment(String newUserId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final enrolledUserId = prefs.getString('biometric_enrolled_user_id');
      if (enrolledUserId != null &&
          enrolledUserId.isNotEmpty &&
          enrolledUserId != newUserId) {
        // Different account logged in → reset biometric (like banking apps)
        await _clearBiometricEnrollment();
      }
    } catch (_) {}
  }

  /// Deletes biometric token + enrolled user ID from storage.
  Future<void> _clearBiometricEnrollment() async {
    try {
      await _secureStorage.delete(key: 'biometric_auth_token_secure');
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('biometric_enrolled_user_id');
      await prefs.setBool('pref_biometrics', false);
      await prefs.setBool('pref_biometric_auto_prompt', false);
    } catch (_) {}
  }

  Future<void> _saveCachedUser(AppUser user) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('cached_user_profile', jsonEncode(user.toJson()));
    } catch (_) {}
  }

  Future<AppUser?> _loadCachedUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString('cached_user_profile');
      if (raw != null && raw.isNotEmpty) {
        final decoded = jsonDecode(raw);
        if (decoded is Map<String, dynamic>) {
          return AppUser.fromJson(decoded);
        }
      }
    } catch (_) {}
    return null;
  }

  Future<void> _clearSession() async {
    await _secureStorage.delete(key: 'auth_token_secure');
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('cached_user_profile');
    } catch (_) {}
    api.clearToken();
    _isLoggedIn = false;
    _user = null;
  }
}
