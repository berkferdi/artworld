import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../constants/app_constants.dart';

class AppTheme {
  AppTheme._();

  static const Color brandRed = Color(AppColors.brandRedValue);
  static const Color nearBlack = Color(AppColors.nearBlackValue);
  static const Color mutedGray = Color(AppColors.mutedGrayValue);
  static const Color softGray = Color(AppColors.softGrayValue);
  static const Color borderGray = Color(AppColors.borderGrayValue);

  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      scaffoldBackgroundColor: Colors.white,
      colorScheme: const ColorScheme.light(
        primary: brandRed,
        onPrimary: Colors.white,
        secondary: nearBlack,
        onSecondary: Colors.white,
        surface: Colors.white,
        onSurface: nearBlack,
        error: brandRed,
      ),
      dividerColor: borderGray,
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: nearBlack,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        centerTitle: true,
        titleTextStyle: GoogleFonts.playfairDisplay(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: nearBlack,
          letterSpacing: -0.2,
        ),
        iconTheme: const IconThemeData(color: nearBlack),
      ),
      drawerTheme: const DrawerThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: Colors.white,
        selectedItemColor: brandRed,
        unselectedItemColor: mutedGray,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
        selectedLabelStyle: TextStyle(fontWeight: FontWeight.w700, fontSize: 11),
        unselectedLabelStyle: TextStyle(fontWeight: FontWeight.w500, fontSize: 11),
      ),
      cardTheme: const CardThemeData(
        color: Colors.white,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.zero,
          side: BorderSide(color: borderGray),
        ),
      ),
      dividerTheme: const DividerThemeData(
        color: borderGray,
        thickness: 1,
        space: 1,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: softGray,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: brandRed, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        hintStyle: GoogleFonts.sourceSans3(
          color: mutedGray,
          fontSize: 15,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: brandRed,
          foregroundColor: Colors.white,
          shape: const RoundedRectangleBorder(borderRadius: BorderRadius.zero),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          textStyle: GoogleFonts.sourceSans3(
            fontWeight: FontWeight.w700,
            fontSize: 14,
            letterSpacing: 0.4,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: brandRed,
          textStyle: GoogleFonts.sourceSans3(fontWeight: FontWeight.w700),
        ),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: brandRed),
    );

    return base.copyWith(
      textTheme: _textTheme(base.textTheme),
    );
  }

  static TextTheme _textTheme(TextTheme base) {
    final display = GoogleFonts.playfairDisplayTextTheme(base);
    final body = GoogleFonts.sourceSans3TextTheme(base);

    return body.copyWith(
      displayLarge: display.displayLarge?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        letterSpacing: -0.5,
      ),
      displayMedium: display.displayMedium?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
      ),
      displaySmall: display.displaySmall?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
      ),
      headlineLarge: display.headlineLarge?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        height: 1.15,
      ),
      headlineMedium: display.headlineMedium?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        height: 1.2,
      ),
      headlineSmall: display.headlineSmall?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        height: 1.25,
      ),
      titleLarge: display.titleLarge?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        fontSize: 20,
      ),
      titleMedium: body.titleMedium?.copyWith(
        fontWeight: FontWeight.w700,
        color: nearBlack,
        fontSize: 16,
      ),
      titleSmall: body.titleSmall?.copyWith(
        fontWeight: FontWeight.w600,
        color: nearBlack,
      ),
      bodyLarge: body.bodyLarge?.copyWith(
        color: nearBlack,
        height: 1.5,
        fontSize: 16,
      ),
      bodyMedium: body.bodyMedium?.copyWith(
        color: nearBlack,
        height: 1.45,
        fontSize: 14,
      ),
      bodySmall: body.bodySmall?.copyWith(
        color: mutedGray,
        height: 1.4,
      ),
      labelLarge: body.labelLarge?.copyWith(
        fontWeight: FontWeight.w700,
        letterSpacing: 0.6,
      ),
    );
  }
}
