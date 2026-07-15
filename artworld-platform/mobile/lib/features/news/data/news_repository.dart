import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final newsRepositoryProvider = Provider<NewsRepository>((ref) {
  return NewsRepository(ref.watch(apiClientProvider));
});

class NewsRepository {
  NewsRepository(this._api);

  final ApiClient _api;

  Future<List<NewsItem>> list({
    String? category,
    bool? breaking,
    int page = 1,
  }) async {
    final query = <String, dynamic>{'page': page};
    if (category != null && category.isNotEmpty) query['category'] = category;
    if (breaking == true) query['breaking'] = 1;

    final response = await _api.get('/news', queryParameters: query);
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => NewsItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<List<NewsItem>> breaking() async {
    final response = await _api.get('/news/breaking');
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => NewsItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<NewsItem> detail(String idOrSlug) async {
    final response = await _api.get('/news/$idOrSlug');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Haber detayı okunamadı.');
    }
    return NewsItem.fromJson(Map<String, dynamic>.from(data));
  }

  Future<void> recordView(int id) async {
    try {
      await _api.post('/news/$id/view');
    } catch (_) {
      // Non-critical analytics — ignore failures.
    }
  }
}

final newsListProvider =
    AsyncNotifierProvider<NewsListNotifier, List<NewsItem>>(NewsListNotifier.new);

class NewsListNotifier extends AsyncNotifier<List<NewsItem>> {
  @override
  Future<List<NewsItem>> build() {
    return ref.read(newsRepositoryProvider).list();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(newsRepositoryProvider).list(),
    );
  }
}

final breakingNewsListProvider =
    FutureProvider.autoDispose<List<NewsItem>>((ref) {
  return ref.watch(newsRepositoryProvider).breaking();
});

final newsDetailProvider =
    FutureProvider.autoDispose.family<NewsItem, String>((ref, idOrSlug) async {
  final repo = ref.watch(newsRepositoryProvider);
  final item = await repo.detail(idOrSlug);
  // fire-and-forget view count
  repo.recordView(item.id);
  return item;
});
