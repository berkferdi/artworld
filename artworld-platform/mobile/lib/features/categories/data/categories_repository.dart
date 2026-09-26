import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final categoriesRepositoryProvider = Provider<CategoriesRepository>((ref) {
  return CategoriesRepository(ref.watch(apiClientProvider));
});

class CategoriesRepository {
  CategoriesRepository(this._api);

  final ApiClient _api;

  Future<List<Category>> list() async {
    final response = await _api.get('/categories');
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => Category.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<({Category category, List<NewsItem> news})> newsBySlug(
    String slug, {
    int page = 1,
  }) async {
    final response = await _api.get(
      '/categories/$slug/news',
      queryParameters: {'page': page},
    );
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Kategori haberleri okunamadı.');
    }
    final map = Map<String, dynamic>.from(data);
    final category = map['category'] is Map
        ? Category.fromJson(Map<String, dynamic>.from(map['category'] as Map))
        : Category(id: 0, name: slug, slug: slug);
    final news = map['news'] is List
        ? (map['news'] as List)
            .whereType<Map>()
            .map((e) => NewsItem.fromJson(Map<String, dynamic>.from(e)))
            .toList()
        : <NewsItem>[];
    return (category: category, news: news);
  }
}

final categoriesListProvider =
    AsyncNotifierProvider<CategoriesListNotifier, List<Category>>(
  CategoriesListNotifier.new,
);

class CategoriesListNotifier extends AsyncNotifier<List<Category>> {
  @override
  Future<List<Category>> build() {
    return ref.read(categoriesRepositoryProvider).list();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(categoriesRepositoryProvider).list(),
    );
  }
}

final categoryNewsProvider = FutureProvider.autoDispose
    .family<({Category category, List<NewsItem> news}), String>((ref, slug) {
  return ref.watch(categoriesRepositoryProvider).newsBySlug(slug);
});
