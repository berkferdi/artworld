import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/content_cards.dart';
import '../../../core/widgets/empty_view.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/loading_skeleton.dart';
import '../../../core/widgets/network_image.dart';
import '../../../core/widgets/section_header.dart';
import '../data/news_repository.dart';

class NewsListScreen extends ConsumerWidget {
  const NewsListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(newsListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.news),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () => context.push('/search'),
          ),
        ],
      ),
      body: async.when(
        loading: () => const ListLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Haberler yüklenemedi.',
          onRetry: () => ref.read(newsListProvider.notifier).refresh(),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyView(
              onRetry: () => ref.read(newsListProvider.notifier).refresh(),
            );
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () => ref.read(newsListProvider.notifier).refresh(),
            child: ListView.separated(
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final item = items[i];
                return NewsListTileCard(
                  item: item,
                  onTap: () => context.push('/news/${item.slug}'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class NewsDetailScreen extends ConsumerWidget {
  const NewsDetailScreen({super.key, required this.idOrSlug});

  final String idOrSlug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(newsDetailProvider(idOrSlug));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Haber'),
        actions: [
          async.maybeWhen(
            data: (news) => IconButton(
              icon: const Icon(Icons.share_outlined),
              onPressed: () {
                Share.share(
                  '${news.title}\n\n${news.summary ?? ''}'.trim(),
                  subject: news.title,
                );
              },
            ),
            orElse: () => const SizedBox.shrink(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const ListLoadingSkeleton(itemCount: 4),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Haber yüklenemedi.',
          onRetry: () => ref.invalidate(newsDetailProvider(idOrSlug)),
        ),
        data: (news) {
          return ListView(
            children: [
              AspectRatio(
                aspectRatio: 16 / 10,
                child: AppNetworkImage(url: news.coverImage),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (news.isBreaking)
                      Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        color: AppTheme.brandRed,
                        child: const Text(
                          'SON DAKİKA',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 11,
                            letterSpacing: 0.5,
                          ),
                        ),
                      ),
                    if (news.category?.name.isNotEmpty ?? false)
                      Text(
                        news.category!.name.toUpperCase(),
                        style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              color: AppTheme.brandRed,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.6,
                            ),
                      ),
                    const SizedBox(height: 8),
                    Text(
                      news.title,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 10),
                    Text(
                      [
                        if (news.author != null && news.author!.isNotEmpty)
                          news.author!,
                        DateFormatters.formatDateTime(news.publishedAt),
                      ].where((e) => e.isNotEmpty).join('  ·  '),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    if (news.summary != null && news.summary!.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Text(
                        news.summary!,
                        style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                      ),
                    ],
                  ],
                ),
              ),
              if (news.content != null && news.content!.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: Html(
                    data: news.content!,
                    style: {
                      'body': Style(
                        fontSize: FontSize(16),
                        lineHeight: LineHeight.number(1.55),
                        color: AppTheme.nearBlack,
                        margin: Margins.symmetric(horizontal: 8),
                      ),
                      'p': Style(margin: Margins.only(bottom: 12)),
                      'a': Style(color: AppTheme.brandRed),
                    },
                  ),
                ),
              if (news.gallery.isNotEmpty) ...[
                const SectionHeader(title: AppStrings.gallery),
                SizedBox(
                  height: 160,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    scrollDirection: Axis.horizontal,
                    itemCount: news.gallery.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 10),
                    itemBuilder: (context, i) {
                      final g = news.gallery[i];
                      return SizedBox(
                        width: 220,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: AppNetworkImage(url: g.imageUrl),
                            ),
                            if (g.caption != null && g.caption!.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  g.caption!,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(height: 12),
              ],
              if (news.related.isNotEmpty) ...[
                const SectionHeader(title: AppStrings.related),
                ...news.related.map(
                  (r) => NewsListTileCard(
                    item: r,
                    onTap: () => context.push('/news/${r.slug}'),
                  ),
                ),
              ],
              const SizedBox(height: 32),
            ],
          );
        },
      ),
    );
  }
}

class BreakingNewsListScreen extends ConsumerWidget {
  const BreakingNewsListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(breakingNewsListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.breakingLabel)),
      body: async.when(
        loading: () => const ListLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Son dakika yüklenemedi.',
          onRetry: () => ref.invalidate(breakingNewsListProvider),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyView(onRetry: () => ref.invalidate(breakingNewsListProvider));
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () async => ref.invalidate(breakingNewsListProvider),
            child: ListView.separated(
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final item = items[i];
                return NewsListTileCard(
                  item: item,
                  onTap: () => context.push('/news/${item.slug}'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
