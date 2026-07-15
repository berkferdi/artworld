import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final searchRepositoryProvider = Provider<SearchRepository>((ref) {
  return SearchRepository(ref.watch(apiClientProvider));
});

class SearchRepository {
  SearchRepository(this._api);

  final ApiClient _api;

  Future<SearchResults> search(String query) async {
    final response = await _api.get('/search', queryParameters: {'q': query});
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Arama sonuçları okunamadı.');
    }
    return SearchResults.fromJson(Map<String, dynamic>.from(data));
  }
}

final searchQueryProvider = StateProvider<String>((ref) => '');

final searchResultsProvider =
    AsyncNotifierProvider<SearchResultsNotifier, SearchResults?>(
  SearchResultsNotifier.new,
);

class SearchResultsNotifier extends AsyncNotifier<SearchResults?> {
  Timer? _debounce;

  @override
  Future<SearchResults?> build() async {
    ref.onDispose(() => _debounce?.cancel());
    return null;
  }

  void onQueryChanged(String raw) {
    final query = raw.trim();
    ref.read(searchQueryProvider.notifier).state = query;
    _debounce?.cancel();

    if (query.length < AppConfig.searchMinChars) {
      state = const AsyncData(null);
      return;
    }

    _debounce = Timer(
      const Duration(milliseconds: AppConfig.searchDebounceMs),
      () => _run(query),
    );
  }

  Future<void> _run(String query) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(searchRepositoryProvider).search(query),
    );
  }

  Future<void> retry() async {
    final q = ref.read(searchQueryProvider);
    if (q.length < AppConfig.searchMinChars) return;
    await _run(q);
  }
}
