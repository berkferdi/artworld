import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/home_repository.dart';
import '../../../core/models/models.dart';

final homeProvider = AsyncNotifierProvider<HomeNotifier, HomeData>(HomeNotifier.new);

class HomeNotifier extends AsyncNotifier<HomeData> {
  @override
  Future<HomeData> build() => _load();

  Future<HomeData> _load() async {
    final repo = ref.read(homeRepositoryProvider);
    // Prefer fresh network; fall back to any cache on failure.
    try {
      return await repo.fetchHome(preferCacheOnError: true);
    } catch (_) {
      final any = await repo.loadAnyCachedHome();
      if (any != null) return any;
      rethrow;
    }
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(_load);
  }
}

final settingsFromHomeProvider = Provider<AppSettings?>((ref) {
  return ref.watch(homeProvider).maybeWhen(
        data: (d) => d.settings,
        orElse: () => null,
      );
});
