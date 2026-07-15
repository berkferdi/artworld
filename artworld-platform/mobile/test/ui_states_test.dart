import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:artworld_mobile/core/constants/app_constants.dart';
import 'package:artworld_mobile/core/theme/app_theme.dart';
import 'package:artworld_mobile/core/widgets/empty_view.dart';
import 'package:artworld_mobile/core/widgets/error_view.dart';
import 'package:artworld_mobile/core/widgets/loading_skeleton.dart';

void main() {
  testWidgets('ErrorView shows Turkish message and retry', (tester) async {
    var retried = false;
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light,
        home: Scaffold(
          body: ErrorView(
            message: 'İnternet bağlantısı yok veya sunucuya ulaşılamıyor.',
            onRetry: () => retried = true,
          ),
        ),
      ),
    );

    expect(
      find.text('İnternet bağlantısı yok veya sunucuya ulaşılamıyor.'),
      findsOneWidget,
    );
    expect(find.text(AppStrings.retry), findsOneWidget);
    await tester.tap(find.text(AppStrings.retry));
    expect(retried, isTrue);
  });

  testWidgets('EmptyView renders message', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light,
        home: const Scaffold(
          body: EmptyView(message: 'Sonuç bulunamadı'),
        ),
      ),
    );
    expect(find.text('Sonuç bulunamadı'), findsOneWidget);
  });

  testWidgets('HomeLoadingSkeleton builds without crash', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(body: HomeLoadingSkeleton()),
      ),
    );
    expect(find.byType(HomeLoadingSkeleton), findsOneWidget);
  });
}
