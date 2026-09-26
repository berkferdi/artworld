import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/models/models.dart';
import '../../../core/services/push_notification_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/network_image.dart';
import '../../home/data/home_repository.dart';
import '../../home/providers/home_providers.dart';
import '../../settings/data/settings_repository.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _fade;
  late final Animation<double> _scale;
  String? _logoUrl;
  String? _error;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _fade = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    _scale = Tween(begin: 0.92, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutBack),
    );
    _controller.forward();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      AppSettings? settings;
      try {
        settings = await ref.read(settingsRepositoryProvider).fetchPublic();
        if (mounted && settings.appLogo != null) {
          setState(() => _logoUrl = settings!.appLogo);
        }
      } catch (_) {
        // Settings will come from home payload.
      }

      await Future.wait([
        ref.read(homeRepositoryProvider).fetchHome(),
        ref.read(pushNotificationServiceProvider).initialize(),
      ]);

      ref.invalidate(homeProvider);
      await ref.read(homeProvider.future);

      if (!mounted) return;
      await Future<void>.delayed(const Duration(milliseconds: 350));
      if (!mounted) return;
      context.go('/');
    } catch (e) {
      try {
        final cached =
            await ref.read(homeRepositoryProvider).loadAnyCachedHome();
        if (cached != null) {
          ref.invalidate(homeProvider);
          if (!mounted) return;
          context.go('/');
          return;
        }
      } catch (_) {}

      if (!mounted) return;
      setState(() {
        _error = 'Başlatılamadı. Bağlantınızı kontrol edin.';
      });
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Colors.white,
              Color(0xFFF7F7F8),
              Color(0xFFFFF5F6),
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 2),
              FadeTransition(
                opacity: _fade,
                child: ScaleTransition(
                  scale: _scale,
                  child: Column(
                    children: [
                      if (_logoUrl != null && _logoUrl!.isNotEmpty)
                        SizedBox(
                          height: 72,
                          child: AppNetworkImage(
                            url: _logoUrl,
                            fit: BoxFit.contain,
                            height: 72,
                          ),
                        )
                      else
                        Container(
                          width: 72,
                          height: 72,
                          color: AppTheme.brandRed,
                          alignment: Alignment.center,
                          child: const Text(
                            'AW',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                              fontSize: 28,
                              letterSpacing: 1,
                            ),
                          ),
                        ),
                      const SizedBox(height: 20),
                      Text(
                        AppStrings.appName,
                        style: Theme.of(context).textTheme.headlineMedium,
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Haber · Canlı Yayın · Programlar',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppTheme.mutedGray,
                              letterSpacing: 0.3,
                            ),
                      ),
                    ],
                  ),
                ),
              ),
              const Spacer(flex: 2),
              if (_error != null) ...[
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Text(
                    _error!,
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () {
                    setState(() => _error = null);
                    _bootstrap();
                  },
                  child: const Text(AppStrings.retry),
                ),
              ] else
                const Padding(
                  padding: EdgeInsets.only(bottom: 8),
                  child: SizedBox(
                    width: 28,
                    height: 28,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: AppTheme.brandRed,
                    ),
                  ),
                ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}
