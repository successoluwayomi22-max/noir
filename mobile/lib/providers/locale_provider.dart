import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocaleProvider extends ChangeNotifier {
  Locale _locale = const Locale('en', 'US');

  Locale get locale => _locale;

  static const Map<String, Locale> localeMap = {
    'English (US)': Locale('en', 'US'),
    'English (UK)': Locale('en', 'GB'),
    'Français': Locale('fr'),
    'Español': Locale('es'),
    'Deutsch': Locale('de'),
    'Italiano': Locale('it'),
    'العربية': Locale('ar'),
    'Hausa': Locale('ha'),
    'Igbo': Locale('ig'),
    'Yoruba': Locale('yo'),
  };

  static const List<String> supportedLanguages = [
    'English (US)',
    'English (UK)',
    'Français',
    'Español',
    'Deutsch',
    'Italiano',
    'العربية',
    'Hausa',
    'Igbo',
    'Yoruba',
  ];

  String _selectedLanguageName = 'English (US)';
  String get selectedLanguageName => _selectedLanguageName;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString('language') ?? 'English (US)';
    _selectedLanguageName = saved;
    _locale = localeMap[saved] ?? const Locale('en', 'US');
    notifyListeners();
  }

  Future<void> setLanguage(String languageName) async {
    if (!localeMap.containsKey(languageName)) return;
    _selectedLanguageName = languageName;
    _locale = localeMap[languageName]!;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('language', languageName);
    notifyListeners();
  }
}
