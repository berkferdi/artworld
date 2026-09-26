import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/content_cards.dart';
import '../../../core/widgets/empty_view.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/loading_skeleton.dart';
import '../data/categories_repository.dart';

class CategoriesScreen extends ConsumerWidget {
  const CategoriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(categoriesListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.categories)),
      body: async.when(
        loading: () => const ListLoadingSkeleton(itemCount: 5),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Kategoriler yüklenemedi.',
          onRetry: () => ref.read(categoriesListProvider.notifier).refresh(),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyView(
              onRetry: () => ref.read(categoriesListProvider.notifier).refresh(),
            );
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () => ref.read(categoriesListProvider.notifier).refresh(),
            child: ListView.separated(
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final c = items[i];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  leading: Container(
                    width: 4,
                    height: 36,
                    color: AppTheme.brandRed,
                  ),
                  title: Text(
                    c.name,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  subtitle: c.description != null && c.description!.isNotEmpty
                      ? Text(c.description!)
                      : null,
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.push('/categories/${c.slug}'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class CategoryNewsScreen extends ConsumerWidget {
  const CategoryNewsScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(categoryNewsProvider(slug));

    return Scaffold(
      appBar: AppBar(
        title: Text(
          async.maybeWhen(
            data: (d) => d.category.name,
            orElse: () => 'Kategori',
          ),
        ),
      ),
      body: async.when(
        loading: () => const ListLoadingSkeleton(),
        error: (e, _) => ErrorView(
          message: e is ApiException ? e.message : 'Kategori haberleri yüklenemedi.',
          onRetry: () => ref.invalidate(categoryNewsProvider(slug)),
        ),
        data: (result) {
          if (result.news.isEmpty) {
            return EmptyView(
              onRetry: () => ref.invalidate(categoryNewsProvider(slug)),
            );
          }
          return RefreshIndicator(
            color: AppTheme.brandRed,
            onRefresh: () async => ref.invalidate(categoryNewsProvider(slug)),
            child: ListView.separated(
              itemCount: result.news.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final item = result.news[i];
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
