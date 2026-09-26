import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:artworld_mobile/core/theme/app_theme.dart';
import 'package:artworld_mobile/core/constants/app_constants.dart';

void main() {
  testWidgets('App theme exposes brand red and title', (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light,
        home: const Scaffold(
          body: Center(child: Text(AppStrings.appName)),
        ),
      ),
    );
    expect(find.text('Art World Mobile'), findsOneWidget);
    expect(AppTheme.brandRed, const Color(0xFFC8102E));
  });
}
