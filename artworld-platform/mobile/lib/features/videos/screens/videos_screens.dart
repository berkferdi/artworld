import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/app_video_player.dart';
import '../../../core/widgets/content_cards.dart';
import '../../../core/widgets/empty_view.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/loading_skeleton.dart';
import '../data/videos_repository.dart';

class VideosListScreen extends ConsumerWidget {
  const VideosListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(videosListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.videos)),
      body: async.when(
        loading: () => const ListLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Videolar yüklenemedi.',
          onRetry: () => ref.read(videosListProvider.notifier).refresh(),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyView(
              onRetry: () => ref.read(videosListProvider.notifier).refresh(),
            );
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () => ref.read(videosListProvider.notifier).refresh(),
            child: GridView.builder(
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 1,
                mainAxisSpacing: 20,
                childAspectRatio: 16 / 12,
              ),
              itemCount: items.length,
              itemBuilder: (context, i) {
                final item = items[i];
                return VideoGridCard(
                  item: item,
                  onTap: () => context.push('/videos/${item.slug}'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class VideoDetailScreen extends ConsumerWidget {
  const VideoDetailScreen({super.key, required this.idOrSlug});

  final String idOrSlug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(videoDetailProvider(idOrSlug));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Video'),
        actions: [
          async.maybeWhen(
            data: (video) => IconButton(
              icon: const Icon(Icons.share_outlined),
              onPressed: () => Share.share(video.title),
            ),
            orElse: () => const SizedBox.shrink(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Video yüklenemedi.',
          onRetry: () => ref.invalidate(videoDetailProvider(idOrSlug)),
        ),
        data: (video) {
          final url = ApiClient.resolveMediaUrl(video.videoUrl);
          return ListView(
            children: [
              if (url.isNotEmpty)
                AppVideoPlayer(
                  url: url,
                  posterUrl: video.thumbnail,
                  title: video.title,
                )
              else
                const ErrorView(message: 'Video adresi bulunamadı.'),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (video.category?.name.isNotEmpty ?? false)
                      Text(
                        video.category!.name.toUpperCase(),
                        style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              color: AppTheme.brandRed,
                              fontWeight: FontWeight.w800,
                            ),
                      ),
                    const SizedBox(height: 8),
                    Text(video.title, style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 8),
                    Text(
                      [
                        if (video.durationSeconds != null)
                          DurationFormatters.fromSeconds(video.durationSeconds),
                        DateFormatters.formatDateTime(video.publishedAt),
                        if (video.videoType != null) video.videoType!.toUpperCase(),
                      ].where((e) => e.isNotEmpty).join('  ·  '),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    if (video.description != null && video.description!.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Text(video.description!),
                    ],
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
