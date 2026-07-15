import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final videosRepositoryProvider = Provider<VideosRepository>((ref) {
  return VideosRepository(ref.watch(apiClientProvider));
});

class VideosRepository {
  VideosRepository(this._api);

  final ApiClient _api;

  Future<List<VideoItem>> list({int page = 1}) async {
    final response = await _api.get('/videos', queryParameters: {'page': page});
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => VideoItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<VideoItem> detail(String idOrSlug) async {
    final response = await _api.get('/videos/$idOrSlug');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Video detayı okunamadı.');
    }
    return VideoItem.fromJson(Map<String, dynamic>.from(data));
  }

  Future<void> recordView(int id) async {
    try {
      await _api.post('/videos/$id/view');
    } catch (_) {}
  }
}

final videosListProvider =
    AsyncNotifierProvider<VideosListNotifier, List<VideoItem>>(
  VideosListNotifier.new,
);

class VideosListNotifier extends AsyncNotifier<List<VideoItem>> {
  @override
  Future<List<VideoItem>> build() {
    return ref.read(videosRepositoryProvider).list();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(videosRepositoryProvider).list(),
    );
  }
}

final videoDetailProvider =
    FutureProvider.autoDispose.family<VideoItem, String>((ref, idOrSlug) async {
  final repo = ref.watch(videosRepositoryProvider);
  final item = await repo.detail(idOrSlug);
  repo.recordView(item.id);
  return item;
});
