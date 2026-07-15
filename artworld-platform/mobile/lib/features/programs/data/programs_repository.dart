import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final programsRepositoryProvider = Provider<ProgramsRepository>((ref) {
  return ProgramsRepository(ref.watch(apiClientProvider));
});

class ProgramsRepository {
  ProgramsRepository(this._api);

  final ApiClient _api;

  Future<List<ProgramItem>> list({int page = 1}) async {
    final response = await _api.get('/programs', queryParameters: {'page': page});
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => ProgramItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<ProgramItem> detail(String idOrSlug) async {
    final response = await _api.get('/programs/$idOrSlug');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Program detayı okunamadı.');
    }
    return ProgramItem.fromJson(Map<String, dynamic>.from(data));
  }

  Future<({ProgramItem program, List<EpisodeItem> episodes})> episodes(
    String idOrSlug, {
    int page = 1,
  }) async {
    final response = await _api.get(
      '/programs/$idOrSlug/episodes',
      queryParameters: {'page': page},
    );
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Bölüm listesi okunamadı.');
    }
    final map = Map<String, dynamic>.from(data);
    final program = map['program'] is Map
        ? ProgramItem.fromJson(Map<String, dynamic>.from(map['program'] as Map))
        : ProgramItem(id: 0, title: '', slug: '');
    final list = map['episodes'] is List
        ? (map['episodes'] as List)
            .whereType<Map>()
            .map((e) => EpisodeItem.fromJson(Map<String, dynamic>.from(e)))
            .toList()
        : <EpisodeItem>[];
    return (program: program, episodes: list);
  }

  Future<EpisodeItem> episodeDetail(String idOrSlug) async {
    final response = await _api.get('/episodes/$idOrSlug');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Bölüm detayı okunamadı.');
    }
    return EpisodeItem.fromJson(Map<String, dynamic>.from(data));
  }

  Future<void> recordEpisodeView(int id) async {
    try {
      await _api.post('/episodes/$id/view');
    } catch (_) {}
  }
}

final programsListProvider =
    AsyncNotifierProvider<ProgramsListNotifier, List<ProgramItem>>(
  ProgramsListNotifier.new,
);

class ProgramsListNotifier extends AsyncNotifier<List<ProgramItem>> {
  @override
  Future<List<ProgramItem>> build() {
    return ref.read(programsRepositoryProvider).list();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(programsRepositoryProvider).list(),
    );
  }
}

final programDetailProvider =
    FutureProvider.autoDispose.family<ProgramItem, String>((ref, idOrSlug) {
  return ref.watch(programsRepositoryProvider).detail(idOrSlug);
});

final episodeDetailProvider =
    FutureProvider.autoDispose.family<EpisodeItem, String>((ref, idOrSlug) async {
  final repo = ref.watch(programsRepositoryProvider);
  final item = await repo.episodeDetail(idOrSlug);
  repo.recordEpisodeView(item.id);
  return item;
});
