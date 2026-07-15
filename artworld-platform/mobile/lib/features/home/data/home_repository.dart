import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/config/app_config.dart';
import '../../../core/models/models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

final homeRepositoryProvider = Provider<HomeRepository>((ref) {
  return HomeRepository(ref.watch(apiClientProvider));
});

class HomeRepository {
  HomeRepository(this._api);

  final ApiClient _api;

  Future<HomeData> fetchHome({bool preferCacheOnError = true}) async {
    try {
      final response = await _api.get('/home');
      final data = response['data'];
      if (data is! Map) {
        throw ApiException(message: 'Ana sayfa verisi okunamadı.');
      }
      final map = Map<String, dynamic>.from(data);
      await _cacheHome(map);
      return HomeData.fromJson(map);
    } catch (e) {
      if (preferCacheOnError) {
        final cached = await loadCachedHome();
        if (cached != null) return cached.copyWith(fromCache: true);
      }
      rethrow;
    }
  }

  Future<HomeData?> loadCachedHome({bool ignoreTtl = false}) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(AppConfig.homeCacheKey);
    final atMs = prefs.getInt(AppConfig.homeCacheAtKey);
    if (raw == null || raw.isEmpty || atMs == null) return null;

    final age = DateTime.now().difference(
      DateTime.fromMillisecondsSinceEpoch(atMs),
    );
    if (!ignoreTtl && age > AppConfig.homeCacheTtl) {
      // Still usable for offline; caller decides. Return null for "fresh" cache miss.
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      return HomeData.fromJson(
        Map<String, dynamic>.from(decoded),
        fromCache: true,
      );
    } catch (_) {
      return null;
    }
  }

  /// Offline: return cached even if TTL expired.
  Future<HomeData?> loadAnyCachedHome() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(AppConfig.homeCacheKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      return HomeData.fromJson(
        Map<String, dynamic>.from(decoded),
        fromCache: true,
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> _cacheHome(Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(AppConfig.homeCacheKey, jsonEncode(data));
    await prefs.setInt(
      AppConfig.homeCacheAtKey,
      DateTime.now().millisecondsSinceEpoch,
    );
  }
}
