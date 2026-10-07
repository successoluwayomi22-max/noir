import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/app_settings.dart';
import '../api_client.dart';

class SettingsProvider with ChangeNotifier {
  final NoirApiClient api;
  AppSettings? _settings;
  bool _isLoading = true;
  String? _error;

  SettingsProvider({required this.api}) {
    _loadCachedSettings().then((_) => fetchSettings());
  }

  AppSettings? get settings => _settings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  // Convenience getter for the store name (fallback to Noir & Co.)
  String get storeName => _settings?.storeName ?? 'Noir & Co.';

  // Short brand name (for compact UI like lock screens, titles)
  String get brandName => storeName;

  Future<void> _loadCachedSettings() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedJson = prefs.getString('cached_app_settings');
      if (cachedJson != null) {
        final data = jsonDecode(cachedJson);
        _settings = AppSettings.fromJson(data);
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> fetchSettings() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final data = await api.getSettings();
      if (data['settings'] != null) {
        _settings = AppSettings.fromJson(data['settings']);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('cached_app_settings', jsonEncode(data['settings']));
      }
    } catch (e) {
      _error = 'Error loading settings: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

