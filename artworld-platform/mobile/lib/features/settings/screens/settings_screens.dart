import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/loading_skeleton.dart';
import '../data/settings_repository.dart';

class AboutScreen extends ConsumerWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(publicSettingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.about)),
      body: async.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            children: [
              LoadingSkeleton(height: 24, width: 180),
              SizedBox(height: 16),
              LoadingSkeleton(height: 120),
            ],
          ),
        ),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Hakkımızda yüklenemedi.',
          onRetry: () => ref.read(publicSettingsProvider.notifier).refresh(),
        ),
        data: (settings) {
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                settings.appName,
                style: Theme.of(context).textTheme.headlineMedium,
              ),
              const SizedBox(height: 16),
              Text(
                settings.aboutText?.trim().isNotEmpty == true
                    ? settings.aboutText!
                    : 'Art World Mobile, haber ve internet televizyonu platformudur.',
                style: Theme.of(context).textTheme.bodyLarge,
              ),
            ],
          );
        },
      ),
    );
  }
}

class ContactScreen extends ConsumerWidget {
  const ContactScreen({super.key});

  Future<void> _launch(String? url) async {
    if (url == null || url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(publicSettingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.contact)),
      body: async.when(
        loading: () => const ListLoadingSkeleton(itemCount: 4),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'İletişim bilgileri yüklenemedi.',
          onRetry: () => ref.read(publicSettingsProvider.notifier).refresh(),
        ),
        data: (settings) {
          return ListView(
            children: [
              if (settings.contactEmail != null)
                ListTile(
                  leading: const Icon(Icons.email_outlined, color: AppTheme.brandRed),
                  title: const Text('E-posta'),
                  subtitle: Text(settings.contactEmail!),
                  onTap: () => _launch('mailto:${settings.contactEmail}'),
                ),
              if (settings.contactPhone != null)
                ListTile(
                  leading: const Icon(Icons.phone_outlined, color: AppTheme.brandRed),
                  title: const Text('Telefon'),
                  subtitle: Text(settings.contactPhone!),
                  onTap: () => _launch(
                    'tel:${settings.contactPhone!.replaceAll(' ', '')}',
                  ),
                ),
              if (settings.websiteUrl != null)
                ListTile(
                  leading: const Icon(Icons.language, color: AppTheme.brandRed),
                  title: const Text('Web Sitesi'),
                  subtitle: Text(settings.websiteUrl!),
                  onTap: () => _launch(settings.websiteUrl),
                ),
              const Divider(),
              ListTile(
                leading: const Icon(Icons.facebook, color: AppTheme.nearBlack),
                title: const Text('Facebook'),
                onTap: () => _launch(settings.facebookUrl),
              ),
              ListTile(
                leading: const Icon(Icons.camera_alt_outlined, color: AppTheme.nearBlack),
                title: const Text('Instagram'),
                onTap: () => _launch(settings.instagramUrl),
              ),
              ListTile(
                leading: const Icon(Icons.ondemand_video, color: AppTheme.nearBlack),
                title: const Text('YouTube'),
                onTap: () => _launch(settings.youtubeUrl),
              ),
              ListTile(
                leading: const Icon(Icons.alternate_email, color: AppTheme.nearBlack),
                title: const Text('X'),
                onTap: () => _launch(settings.xUrl),
              ),
            ],
          );
        },
      ),
    );
  }
}
