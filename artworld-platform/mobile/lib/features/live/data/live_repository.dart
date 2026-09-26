import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final liveRepositoryProvider = Provider<LiveRepository>((ref) {
  return LiveRepository(ref.watch(apiClientProvider));
});

class LiveRepository {
  LiveRepository(this._api);

  final ApiClient _api;

  Future<LiveStream> fetch() async {
    final response = await _api.get('/live');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Canlı yayın bilgisi okunamadı.');
    }
    return LiveStream.fromJson(Map<String, dynamic>.from(data));
  }
}

final liveStreamProvider =
    AsyncNotifierProvider<LiveStreamNotifier, LiveStream>(LiveStreamNotifier.new);

class LiveStreamNotifier extends AsyncNotifier<LiveStream> {
  @override
  Future<LiveStream> build() {
    return ref.read(liveRepositoryProvider).fetch();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(liveRepositoryProvider).fetch(),
    );
  }
}
