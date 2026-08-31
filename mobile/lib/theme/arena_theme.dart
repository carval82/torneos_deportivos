import 'package:flutter/material.dart';

class ArenaColors {
  static const navy = Color(0xFF0B1F3A);
  static const ink = Color(0xFF152A4A);
  static const lime = Color(0xFFA8E63D);
  static const limeDark = Color(0xFF7CC41A);
  static const mist = Color(0xFFF4F7F2);
}

ThemeData buildArenaTheme() {
  return ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: ArenaColors.navy,
      primary: ArenaColors.navy,
      secondary: ArenaColors.lime,
      surface: Colors.white,
      brightness: Brightness.light,
    ),
    scaffoldBackgroundColor: ArenaColors.mist,
    appBarTheme: const AppBarTheme(
      backgroundColor: ArenaColors.navy,
      foregroundColor: Colors.white,
      elevation: 0,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: ArenaColors.lime,
        foregroundColor: ArenaColors.navy,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: ArenaColors.navy,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: ArenaColors.limeDark, width: 2),
      ),
    ),
    cardTheme: CardTheme(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(color: Colors.black.withOpacity(0.06)),
      ),
    ),
  );
}
