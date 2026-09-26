import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/connectivity.dart';
import '../../../core/widgets/content_cards.dart';
import '../../../core/widgets/empty_view.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/loading_skeleton.dart';
import '../../../core/widgets/network_image.dart';
import '../../../core/widgets/section_header.dart';
import '../providers/home_providers.dart';
import '../widgets/breaking_ticker.dart';
import '../widgets/home_hero_slider.dart';
import '../../shell/shell_scaffold_key.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);
    final online = ref.watch(isOnlineProvider);
    final scaffoldKey = ref.watch(shellScaffoldKeyProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu),
          onPressed: () => scaffoldKey.currentState?.openDrawer(),
        ),
        title: _LogoTitle(logoUrl: homeAsync.valueOrNull?.settings.appLogo),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () => context.push('/search'),
          ),
        ],
      ),
      body: homeAsync.when(
        loading: () => const HomeLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Ana sayfa yüklenemedi.',
          onRetry: () => ref.read(homeProvider.notifier).refresh(),
        ),
        data: (data) {
          final showOffline = data.fromCache || !online;
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () => ref.read(homeProvider.notifier).refresh(),
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                if (showOffline)
                  const SliverToBoxAdapter(child: OfflineBanner()),
                SliverToBoxAdapter(
                  child: HomeHeroSlider(
                    featuredNews: data.featuredNews,
                    banners: data.banners,
                  ),
                ),
                if (data.settings.liveStreamEnabled && data.liveStream != null)
                  const SliverToBoxAdapter(child: LiveWatchCta()),
                if (data.settings.breakingNewsEnabled &&
                    data.breakingNews.isNotEmpty)
                  SliverToBoxAdapter(
                    child: BreakingNewsTicker(items: data.breakingNews),
                  ),
                if (data.featuredNews.isNotEmpty) ...[
                  const ConfrontSection(
                    child: SectionHeader(title: AppStrings.featuredNews),
                  ),
                  SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, i) {
                        final item = data.featuredNews[i];
                        return NewsListTileCard(
                          item: item,
                          onTap: () => context.push('/news/${item.slug}'),
                        );
                      },
                      childCount: data.featuredNews.length.clamp(0, 3),
                    ),
                  ),
                ],
                if (data.latestNews.isNotEmpty) ...[
                  ConfrontSection(
                    child: SectionHeader(
                      title: AppStrings.latestNews,
                      onSeeAll: () => context.go('/news'),
                    ),
                  ),
                  SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, i) {
                        final item = data.latestNews[i];
                        return Column(
                          children: [
                            NewsListTileCard(
                              item: item,
                              onTap: () => context.push('/news/${item.slug}'),
                            ),
                            const Divider(height: 1),
                          ],
                        );
                      },
                      childCount: data.latestNews.length,
                    ),
                  ),
                ],
                if (data.latestVideos.isNotEmpty) ...[
                  ConfrontSection(
                    child: SectionHeader(
                      title: AppStrings.latestVideos,
                      onSeeAll: () => context.go('/videos'),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: SizedBox(
                      height: 180,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        scrollDirection: Axis.horizontal,
                        itemCount: data.latestVideos.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, i) {
                          final v = data.latestVideos[i];
                          return SizedBox(
                            width: 240,
                            child: VideoGridCard(
                              item: v,
                              onTap: () => context.push('/videos/${v.slug}'),
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                ],
                if (data.programs.isNotEmpty) ...[
                  ConfrontSection(
                    child: SectionHeader(
                      title: AppStrings.programs,
                      onSeeAll: () => context.go('/programs'),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: SizedBox(
                      height: 260,
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                        scrollDirection: Axis.horizontal,
                        itemCount: data.programs.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, i) {
                          final p = data.programs[i];
                          return ProgramCard(
                            item: p,
                            onTap: () => context.push('/programs/${p.slug}'),
                          );
                        },
                      ),
                    ),
                  ),
                ],
                if (data.featuredNews.isEmpty &&
                    data.latestNews.isEmpty &&
                    data.latestVideos.isEmpty &&
                    data.programs.isEmpty)
                  const SliverFillRemaining(
                    hasScrollBody: false,
                    child: EmptyView(),
                  ),
                const SliverToBoxAdapter(child: SizedBox(height: 24)),
              ],
            ),
          );
        },
      ),
    );
  }
}

/// Helper to wrap non-sliver widgets into slivers without polluting callers.
class ConfrontSection extends StatelessWidget {
  const ConfrontSection({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) => SliverToBoxAdapter(child: child);
}

class _LogoTitle extends StatelessWidget {
  const _LogoTitle({this.logoUrl});

  final String? logoUrl;

  @override
  Widget build(BuildContext context) {
    if (logoUrl != null && logoUrl!.isNotEmpty) {
      return SizedBox(
        height: 32,
        child: AppNetworkImage(
          url: logoUrl,
          fit: BoxFit.contain,
          height: 32,
        ),
      );
    }
    return Text(
      AppStrings.appName,
      style: Theme.of(context).textTheme.titleLarge,
    );
  }
}
