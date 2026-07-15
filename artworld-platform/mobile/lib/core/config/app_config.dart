import 'package:flutter/foundation.dart';

/// Application configuration.
///
/// For production, change [apiBaseUrl] / [mediaBaseUrl] to your HTTPS endpoints:
/// ```dart
/// static const String apiBaseUrl = 'https://api.artworld.example/api/v1';
/// static const String mediaBaseUrl = 'https://api.artworld.example';
/// ```
class AppConfig {
  AppConfig._();

  /// REST API base (includes /api/v1). Change for production HTTPS.
  static const String apiBaseUrl = 'http://127.0.0.1:8080/api/v1';

  /// Origin used to resolve relative media paths if the API returns them.
  static const String mediaBaseUrl = 'http://127.0.0.1:8080';

  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 30);
  static const Duration sendTimeout = Duration(seconds: 20);

  /// Home response SharedPreferences cache TTL.
  static const Duration homeCacheTtl = Duration(minutes: 5);

  static const String homeCacheKey = 'home_cache_v1';
  static const String homeCacheAtKey = 'home_cache_at_v1';
  static const String deviceUuidKey = 'device_uuid_v1';

  static const int searchDebounceMs = 300;
  static const int searchMinChars = 2;

  /// True only in debug builds — used for Dio logging etc.
  static bool get isDebug => kDebugMode;

  static const String appName = 'Art World Mobile';
  static const String brandRed = '#C8102E';
}
