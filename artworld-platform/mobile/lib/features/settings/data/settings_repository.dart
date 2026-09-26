import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../home/providers/home_providers.dart';

final settingsRepositoryProvider = Provider<SettingsRepository>((ref) {
  return SettingsRepository(ref.watch(apiClientProvider));
});

class SettingsRepository {
  SettingsRepository(this._api);

  final ApiClient _api;

  Future<AppSettings> fetchPublic() async {
    final response = await _api.get('/settings/public');
    final data = response['data'];
    if (data is! Map) {
      throw ApiException(message: 'Ayarlar okunamadı.');
    }
    return AppSettings.fromJson(Map<String, dynamic>.from(data));
  }
}

final publicSettingsProvider =
    AsyncNotifierProvider<PublicSettingsNotifier, AppSettings>(
  PublicSettingsNotifier.new,
);

class PublicSettingsNotifier extends AsyncNotifier<AppSettings> {
  @override
  Future<AppSettings> build() async {
    // Prefer settings already loaded with home to avoid a duplicate request.
    final fromHome = ref.watch(settingsFromHomeProvider);
    if (fromHome != null) return fromHome;
    return ref.read(settingsRepositoryProvider).fetchPublic();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(settingsRepositoryProvider).fetchPublic(),
    );
  }
}
