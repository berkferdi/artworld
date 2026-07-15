import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

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
import '../../../core/widgets/network_image.dart';
import '../../../core/widgets/section_header.dart';
import '../data/programs_repository.dart';

class ProgramsListScreen extends ConsumerWidget {
  const ProgramsListScreen({super.key, this.pastOnly = false});

  final bool pastOnly;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(programsListProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(pastOnly ? AppStrings.pastPrograms : AppStrings.programs),
      ),
      body: async.when(
        loading: () => const ListLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Programlar yüklenemedi.',
          onRetry: () => ref.read(programsListProvider.notifier).refresh(),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyView(
              onRetry: () => ref.read(programsListProvider.notifier).refresh(),
            );
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () => ref.read(programsListProvider.notifier).refresh(),
            child: GridView.builder(
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 16,
                crossAxisSpacing: 12,
                childAspectRatio: 0.62,
              ),
              itemCount: items.length,
              itemBuilder: (context, i) {
                final item = items[i];
                return ProgramCard(
                  width: double.infinity,
                  item: item,
                  onTap: () => context.push('/programs/${item.slug}'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class ProgramDetailScreen extends ConsumerWidget {
  const ProgramDetailScreen({super.key, required this.idOrSlug});

  final String idOrSlug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(programDetailProvider(idOrSlug));

    return Scaffold(
      appBar: AppBar(title: const Text('Program')),
      body: async.when(
        loading: () => const ListLoadingSkeleton(itemCount: 4),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Program yüklenemedi.',
          onRetry: () => ref.invalidate(programDetailProvider(idOrSlug)),
        ),
        data: (program) {
          return ListView(
            children: [
              AspectRatio(
                aspectRatio: 16 / 9,
                child: AppNetworkImage(url: program.coverImage),
              ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(program.title, style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 8),
                    Text(
                      [
                        if (program.presenter != null) program.presenter!,
                        if (program.broadcastDay != null) program.broadcastDay!,
                        if (program.broadcastTime != null) program.broadcastTime!,
                      ].whereType<String>().where((e) => e.isNotEmpty).join('  ·  '),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    if (program.description != null &&
                        program.description!.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Text(program.description!),
                    ],
                  ],
                ),
              ),
              const SectionHeader(title: AppStrings.episodes),
              if (program.episodes.isEmpty)
                const Padding(
                  padding: EdgeInsets.all(24),
                  child: EmptyView(message: 'Henüz bölüm eklenmemiş.'),
                )
              else
                ...program.episodes.map((ep) {
                  return ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                    leading: SizedBox(
                      width: 96,
                      height: 64,
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          AppNetworkImage(url: ep.thumbnail),
                          const Center(
                            child: Icon(Icons.play_circle_fill, color: Colors.white),
                          ),
                        ],
                      ),
                    ),
                    title: Text(
                      ep.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      [
                        if (ep.episodeNumber != null) 'Bölüm ${ep.episodeNumber}',
                        DurationFormatters.fromSeconds(ep.durationSeconds),
                        DateFormatters.formatDate(ep.publishedAt),
                      ].where((e) => e.isNotEmpty).join(' · '),
                    ),
                    onTap: () => context.push('/episodes/${ep.slug}'),
                  );
                }),
              const SizedBox(height: 24),
            ],
          );
        },
      ),
    );
  }
}

class EpisodePlayerScreen extends ConsumerWidget {
  const EpisodePlayerScreen({super.key, required this.idOrSlug});

  final String idOrSlug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(episodeDetailProvider(idOrSlug));

    return Scaffold(
      appBar: AppBar(title: const Text('Bölüm')),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Bölüm yüklenemedi.',
          onRetry: () => ref.invalidate(episodeDetailProvider(idOrSlug)),
        ),
        data: (episode) {
          final url = ApiClient.resolveMediaUrl(episode.videoUrl);
          return ListView(
            children: [
              if (url.isNotEmpty)
                AppVideoPlayer(url: url, title: episode.title)
              else
                const ErrorView(message: 'Video adresi bulunamadı.'),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      episode.title,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      [
                        if (episode.episodeNumber != null)
                          'Bölüm ${episode.episodeNumber}',
                        DurationFormatters.fromSeconds(episode.durationSeconds),
                        DateFormatters.formatDateTime(episode.publishedAt),
                      ].where((e) => e.isNotEmpty).join('  ·  '),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    if (episode.description != null &&
                        episode.description!.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Text(episode.description!),
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
