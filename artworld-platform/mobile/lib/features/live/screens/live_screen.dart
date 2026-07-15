import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_video_player.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/network_image.dart';
import '../data/live_repository.dart';

class LiveScreen extends ConsumerWidget {
  const LiveScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(liveStreamProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.liveTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.read(liveStreamProvider.notifier).refresh(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(
          child: CircularProgressIndicator(color: AppTheme.brandRed),
        ),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Canlı yayın yüklenemedi.',
          onRetry: () => ref.read(liveStreamProvider.notifier).refresh(),
        ),
        data: (live) {
          final url = ApiClient.resolveMediaUrl(live.streamUrl);
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                color: AppTheme.brandRed,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      live.isActive ? 'CANLI' : 'YAYIN BEKLENİYOR',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.6,
                      ),
                    ),
                  ],
                ),
              ),
              if (url.isNotEmpty)
                AppVideoPlayer(
                  key: ValueKey(url),
                  url: url,
                  autoPlay: live.isActive,
                  posterUrl: live.posterImage,
                  title: live.title,
                )
              else
                AspectRatio(
                  aspectRatio: 16 / 9,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      AppNetworkImage(url: live.posterImage),
                      ErrorView(
                        message: 'Yayın adresi bulunamadı.',
                        onRetry: () =>
                            ref.read(liveStreamProvider.notifier).refresh(),
                      ),
                    ],
                  ),
                ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Text(
                      live.title,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    if (live.description != null &&
                        live.description!.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Text(live.description!),
                    ],
                    const SizedBox(height: 20),
                    OutlinedButton.icon(
                      onPressed: () =>
                          ref.read(liveStreamProvider.notifier).refresh(),
                      icon: const Icon(Icons.refresh),
                      label: const Text('Yayını Yenile'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.brandRed,
                        side: const BorderSide(color: AppTheme.brandRed),
                        shape: const RoundedRectangleBorder(
                          borderRadius: BorderRadius.zero,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
