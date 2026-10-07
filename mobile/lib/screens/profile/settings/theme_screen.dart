import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import '../../../l10n/app_strings.dart';
import '../../../providers/theme_provider.dart';

class ThemeScreen extends StatefulWidget {
  const ThemeScreen({super.key});

  @override
  State<ThemeScreen> createState() => _ThemeScreenState();
}

class _ThemeScreenState extends State<ThemeScreen> {
  String _selectedTheme = 'System default';
  final List<String> _themeKeys = ['System default', 'Light', 'Dark'];

  @override
  void initState() {
    super.initState();
    _loadTheme();
  }

  Future<void> _loadTheme() async {
    final prefs = await SharedPreferences.getInstance();
    if (mounted) {
      setState(() {
        _selectedTheme = prefs.getString('theme') ?? 'System default';
      });
    }
  }

  Future<void> _saveTheme(String theme) async {
    final prov = Provider.of<ThemeProvider>(context, listen: false);
    String mappedTheme = 'system';
    if (theme == 'Light') mappedTheme = 'light';
    if (theme == 'Dark') mappedTheme = 'dark';

    await prov.setTheme(mappedTheme);
    if (mounted) {
      setState(() {
        _selectedTheme = theme;
      });
    }
  }

  String _getTranslatedTheme(String key, AppStrings strings) {
    switch (key) {
      case 'Light':
        return strings.themeLight;
      case 'Dark':
        return strings.themeDark;
      default:
        return strings.themeSystem;
    }
  }

  @override
  Widget build(BuildContext context) {
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
        title: Text(strings.theme, style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
      ),
      body: ListView.separated(
        itemCount: _themeKeys.length,
        separatorBuilder: (_, _) => Divider(height: 1, color: Theme.of(context).colorScheme.outline),
        itemBuilder: (context, index) {
          final themeKey = _themeKeys[index];
          final translatedLabel = _getTranslatedTheme(themeKey, strings);
          return ListTile(
            title: Text(translatedLabel, style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w600)),
            trailing: _selectedTheme == themeKey ? Icon(Icons.check_rounded, color: Theme.of(context).colorScheme.primary) : null,
            onTap: () async {
              final nav = Navigator.of(context);
              await _saveTheme(themeKey);
              if (mounted) {
                Future.delayed(Duration(milliseconds: 300), () {
                  if (mounted) nav.pop();
                });
              }
            },
          );
        },
      ),
    );
  }
}
