import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/app_config.dart';
import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/content_cards.dart';
import '../../../core/widgets/empty_view.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/section_header.dart';
import '../data/search_repository.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  late final TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: ref.read(searchQueryProvider));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final resultsAsync = ref.watch(searchResultsProvider);
    final query = ref.watch(searchQueryProvider);

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: TextField(
          controller: _controller,
          autofocus: true,
          textInputAction: TextInputAction.search,
          decoration: const InputDecoration(
            hintText: AppStrings.searchHint,
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
            filled: false,
          ),
          onChanged: (v) =>
              ref.read(searchResultsProvider.notifier).onQueryChanged(v),
        ),
        actions: [
          if (_controller.text.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.clear),
              onPressed: () {
                _controller.clear();
                ref.read(searchResultsProvider.notifier).onQueryChanged('');
              },
            ),
        ],
      ),
      body: Builder(
        builder: (context) {
          if (query.length < AppConfig.searchMinChars) {
            return const EmptyView(
              message: AppStrings.searchMinHint,
              icon: Icons.search,
            );
          }

          return resultsAsync.when(
            loading: () => const Center(
              child: CircularProgressIndicator(color: AppTheme.brandRed),
            ),
            error: (e, _) => ErrorView(
              message: e is ApiException ? e.message : 'Arama başarısız.',
              onRetry: () => ref.read(searchResultsProvider.notifier).retry(),
            ),
            data: (results) {
              if (results == null) {
                return const EmptyView(
                  message: AppStrings.searchMinHint,
                  icon: Icons.search,
                );
              }
              if (results.isEmpty) {
                return const EmptyView(
                  message: AppStrings.noResults,
                  icon: Icons.search_off,
                );
              }

              return ListView(
                children: [
                  if (results.news.isNotEmpty) ...[
                    const SectionHeader(title: AppStrings.news),
                    ...results.news.map(
                      (n) => NewsListTileCard(
                        item: n,
                        onTap: () => context.push('/news/${n.slug}'),
                      ),
                    ),
                  ],
                  if (results.videos.isNotEmpty) ...[
                    const SectionHeader(title: AppStrings.videos),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Column(
                        children: results.videos
                            .map(
                              (v) => Padding(
                                padding: const EdgeInsets.only(bottom: 16),
                                child: VideoGridCard(
                                  item: v,
                                  onTap: () =>
                                      context.push('/videos/${v.slug}'),
                                ),
                              ),
                            )
                            .toList(),
                      ),
                    ),
                  ],
                  if (results.programs.isNotEmpty) ...[
                    const SectionHeader(title: AppStrings.programs),
                    SizedBox(
                      height: 260,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        scrollDirection: Axis.horizontal,
                        itemCount: results.programs.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, i) {
                          final p = results.programs[i];
                          return ProgramCard(
                            item: p,
                            onTap: () => context.push('/programs/${p.slug}'),
                          );
                        },
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                ],
              );
            },
          );
        },
      ),
    );
  }
}
