import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../l10n/app_strings.dart';
import '../../providers/settings_provider.dart';
import '../../providers/locale_provider.dart';
import 'package:provider/provider.dart';
import 'settings/about_screen.dart';
import 'settings/language_screen.dart';
import 'settings/privacy_security_screen.dart';
import 'settings/theme_screen.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  String _theme = 'System default';

  @override
  void initState() {
    super.initState();
    _loadTheme();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadTheme();
  }

  Future<void> _loadTheme() async {
    final prefs = await SharedPreferences.getInstance();
    if (mounted) {
      setState(() {
        _theme = prefs.getString('theme') ?? 'System default';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    // Watch LocaleProvider directly so language subtitle updates immediately
    final localeProvider = context.watch<LocaleProvider>();
    final currentLanguage = localeProvider.selectedLanguageName;
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(strings.settings,
            style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
      ),
      body: ListView(
        padding: EdgeInsets.symmetric(vertical: 24),
        children: [
          _buildSettingsTile(
            Icons.language_rounded,
            strings.language,
            currentLanguage, // ← Now reads directly from LocaleProvider
            () async {
              await Navigator.push(
                  context, MaterialPageRoute(builder: (_) => LanguageScreen()));
              _loadTheme();
            },
          ),
          _buildSettingsTile(Icons.dark_mode_outlined, strings.theme, _theme, () async {
            await Navigator.push(
                context, MaterialPageRoute(builder: (_) => ThemeScreen()));
            _loadTheme();
          }),
          _buildSettingsTile(
            Icons.lock_outline_rounded,
            strings.privacySecurity,
            null,
            () => Navigator.push(
                context, MaterialPageRoute(builder: (_) => PrivacySecurityScreen())),
          ),
          _buildSettingsTile(
            Icons.info_outline_rounded,
            strings.aboutStore(context.watch<SettingsProvider>().brandName),
            'v1.0.0',
            () => Navigator.push(
                context, MaterialPageRoute(builder: (_) => AboutScreen())),
          ),
        ],
      ),
    );
  }

  Widget _buildSettingsTile(IconData icon, String title, String? subtitle, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, color: Theme.of(context).colorScheme.onSurface),
      title: Text(title,
          style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w600)),
      subtitle: subtitle != null
          ? Text(subtitle,
              style: GoogleFonts.urbanist(
                  fontSize: 14,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)))
          : null,
      trailing: Icon(Icons.chevron_right_rounded,
          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
      onTap: onTap,
    );
  }
}
