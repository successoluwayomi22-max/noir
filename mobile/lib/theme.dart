import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class NoirTheme {
  // Official Noir Signature Palette
  static Color background = Color(0xFFFFFFFF); // Clean White Canvas
  static Color surface = Color(0xFFF7F7F2);    // Signature Warm Ivory Paper
  static Color surfaceWarm = Color(0xFFF7F7F2); // Warm App Bar / Section
  static Color primary = Color(0xFF687158);    // Signature Noir Olive Green
  static Color primaryDark = Color(0xFF252820); // Deep Noir Charcoal
  static Color primaryOlive = Color(0xFF687158); // Signature Olive
  static Color accent = Color(0xFF555D48);     // Deep Olive Accent
  static Color inkSoft = Color(0xFF353A35);    // Soft Charcoal Ink
  static Color textPrimary = Color(0xFF252820); // Rich Noir Dark
  static Color textSecondary = Color(0xFF686B62); // Sophisticated Muted
  static Color textMuted = Color(0xFF8F9187);
  static Color divider = Color(0xFFE2E3DB);    // Warm Light Stone Divider
  static Color error = Color(0xFFB00020);
  static Color success = Color(0xFF3D6B42);

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      scaffoldBackgroundColor: background,
      colorScheme: ColorScheme.light(
        primary: primary,
        secondary: primaryDark,
        tertiary: accent,
        surface: surface,
        error: error,
        onPrimary: Colors.white,
        onSecondary: Colors.white,
        onSurface: textPrimary,
        outline: divider,
      ),
      textTheme: GoogleFonts.urbanistTextTheme().copyWith(
        displayLarge: GoogleFonts.urbanist(
          fontSize: 32,
          fontWeight: FontWeight.w700,
          color: textPrimary,
          letterSpacing: 0,
        ),
        displayMedium: GoogleFonts.urbanist(
          fontSize: 28,
          fontWeight: FontWeight.w700,
          color: textPrimary,
          letterSpacing: 0,
        ),
        headlineLarge: GoogleFonts.urbanist(
          fontSize: 24,
          fontWeight: FontWeight.w600,
          color: textPrimary,
        ),
        headlineMedium: GoogleFonts.urbanist(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: textPrimary,
        ),
        titleLarge: GoogleFonts.urbanist(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: textPrimary,
        ),
        titleMedium: GoogleFonts.urbanist(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: textPrimary,
        ),
        bodyLarge: GoogleFonts.urbanist(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: textPrimary,
        ),
        bodyMedium: GoogleFonts.urbanist(
          fontSize: 14,
          fontWeight: FontWeight.w400,
          color: textSecondary,
        ),
        bodySmall: GoogleFonts.urbanist(
          fontSize: 12,
          fontWeight: FontWeight.w400,
          color: textMuted,
        ),
        labelLarge: GoogleFonts.urbanist(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: textPrimary,
          letterSpacing: 0.6,
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
        iconTheme: IconThemeData(color: textPrimary, size: 22),
        titleTextStyle: GoogleFonts.urbanist(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: textPrimary,
          letterSpacing: 0.4,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryDark,
          foregroundColor: Colors.white,
          minimumSize: const Size(64, 50),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: GoogleFonts.urbanist(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.8,
          ),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryDark,
          minimumSize: const Size(64, 50),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          side: BorderSide(color: divider, width: 1.5),
          textStyle: GoogleFonts.urbanist(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.8,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        contentPadding: EdgeInsets.symmetric(
          horizontal: 20,
          vertical: 16,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: divider),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: divider),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: primaryDark, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: error),
        ),
        hintStyle: GoogleFonts.urbanist(fontSize: 14, color: textMuted),
        labelStyle: GoogleFonts.urbanist(fontSize: 14, color: textSecondary),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: surface,
        selectedItemColor: primaryDark,
        unselectedItemColor: textMuted,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        showUnselectedLabels: true,
      ),
      cardTheme: CardThemeData(
        color: surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: divider, width: 0.5),
        ),
      ),
      dividerTheme: DividerThemeData(color: divider, thickness: 0.5),
    );
  }

  // Luxury Charcoal & Soft Olive Theme Palette
  static const Color charcoalBg = Color(0xFF121412);       // Deep Charcoal Canvas
  static const Color charcoalSurface = Color(0xFF1B1E1A);  // Refined Charcoal Surface
  static const Color charcoalCard = Color(0xFF212520);     // Elevated Charcoal Product Card
  static const Color softOlive = Color(0xFF98A682);        // Sophisticated Soft Olive (for numbers, prices, & highlights)
  static const Color softOliveLight = Color(0xFFC3CFB2);   // Light Soft Olive Accent
  static const Color softOliveSubtle = Color(0xFF383E31);  // Subtle Soft Olive Badge Tint
  static const Color whiteText = Color(0xFFFFFFFF);        // Pure White Text
  static const Color mutedWhiteText = Color(0xFFD2D6CC);   // Soft Crisp White Text

  /// Dark theme — Charcoal background, Pure White text, and Soft Olive accents/numbers.
  static ThemeData get darkTheme {
    const bgDark = charcoalBg;
    const surfaceDark = charcoalSurface;
    const surfaceDark2 = charcoalCard;
    const textPrimDark = whiteText;
    const textSecDark = mutedWhiteText;
    const dividerDark = Color(0xFF2D322B);
    const oliveAccent = softOlive;

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: bgDark,
      colorScheme: const ColorScheme.dark(
        primary: oliveAccent,
        secondary: softOliveLight,
        tertiary: Color(0xFFD4AF37), // Subtle Warm Gold
        surface: surfaceDark,
        error: Color(0xFFEF4444),
        onPrimary: Color(0xFF121412),
        onSecondary: Color(0xFF121412),
        onSurface: textPrimDark,
        outline: dividerDark,
      ),
      textTheme: GoogleFonts.urbanistTextTheme(ThemeData.dark().textTheme).copyWith(
        displayLarge: GoogleFonts.urbanist(fontSize: 32, fontWeight: FontWeight.w800, color: textPrimDark),
        displayMedium: GoogleFonts.urbanist(fontSize: 28, fontWeight: FontWeight.w800, color: textPrimDark),
        headlineLarge: GoogleFonts.urbanist(fontSize: 24, fontWeight: FontWeight.w700, color: textPrimDark),
        headlineMedium: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.w700, color: textPrimDark),
        titleLarge: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w700, color: textPrimDark),
        titleMedium: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w600, color: textPrimDark),
        bodyLarge: GoogleFonts.urbanist(fontSize: 16, color: textPrimDark),
        bodyMedium: GoogleFonts.urbanist(fontSize: 14, color: textSecDark),
        bodySmall: GoogleFonts.urbanist(fontSize: 12, color: textSecDark),
        labelLarge: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w700, color: textPrimDark, letterSpacing: 0.6),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: bgDark,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: textPrimDark, size: 22),
        titleTextStyle: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800, color: textPrimDark, letterSpacing: 0.4),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: oliveAccent,
          foregroundColor: const Color(0xFF121412),
          minimumSize: const Size(64, 50),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w800, letterSpacing: 0.8),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: textPrimDark,
          minimumSize: const Size(64, 50),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          side: const BorderSide(color: dividerDark, width: 1.5),
          textStyle: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w700, letterSpacing: 0.8),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surfaceDark2,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: dividerDark)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: dividerDark)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: oliveAccent, width: 1.5)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFEF4444))),
        hintStyle: GoogleFonts.urbanist(fontSize: 14, color: textSecDark),
        labelStyle: GoogleFonts.urbanist(fontSize: 14, color: textSecDark),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: surfaceDark,
        selectedItemColor: oliveAccent,
        unselectedItemColor: textSecDark,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        showUnselectedLabels: true,
      ),
      cardTheme: CardThemeData(
        color: surfaceDark2,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: dividerDark, width: 0.5),
        ),
      ),
      dividerTheme: const DividerThemeData(color: dividerDark, thickness: 0.5),
    );
  }
}
