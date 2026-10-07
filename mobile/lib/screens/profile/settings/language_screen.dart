import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../../providers/locale_provider.dart';

import '../../../l10n/app_strings.dart';

class LanguageScreen extends StatelessWidget {
  const LanguageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final localeProvider = context.watch<LocaleProvider>();
    final selected = localeProvider.selectedLanguageName;
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
        title: Text(strings.language, style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
      ),
      body: ListView.separated(
        itemCount: LocaleProvider.supportedLanguages.length,
        separatorBuilder: (_, _) => Divider(height: 1, color: Theme.of(context).colorScheme.outline),
        itemBuilder: (context, index) {
          final lang = LocaleProvider.supportedLanguages[index];
          final isSelected = selected == lang;
          return ListTile(
            contentPadding: EdgeInsets.symmetric(horizontal: 24, vertical: 4),
            title: Text(
              lang,
              style: GoogleFonts.urbanist(
                fontSize: 16,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                color: isSelected ? Theme.of(context).colorScheme.primary : Theme.of(context).colorScheme.onSurface,
              ),
            ),
            trailing: isSelected
                ? Icon(Icons.check_rounded, color: Theme.of(context).colorScheme.primary)
                : null,
            onTap: () async {
              await context.read<LocaleProvider>().setLanguage(lang);
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Language changed to $lang',
                        style: GoogleFonts.urbanist()),
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    behavior: SnackBarBehavior.floating,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    duration: Duration(seconds: 2),
                  ),
                );
                await Future.delayed(Duration(milliseconds: 400));
                if (context.mounted) Navigator.pop(context);
              }
            },
          );
        },
      ),
    );
  }
}
