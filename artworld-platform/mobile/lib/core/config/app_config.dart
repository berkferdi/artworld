import 'package:flutter/foundation.dart';

/// Application configuration.
///
/// Override at build/run time without changing source:
/// ```bash
/// flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080/api/v1 \
///             --dart-define=MEDIA_BASE_URL=http://10.0.2.2:8080
/// ```
///
/// Production example:
/// ```bash
/// flutter build apk --dart-define=API_BASE_URL=https://api.example.com/api/v1 \
///                   --dart-define=MEDIA_BASE_URL=https://media.example.com
/// ```
class AppConfig {
  AppConfig._();

  static const String _defaultApiBaseUrl = 'http://127.0.0.1:8080/api/v1';
  static const String _defaultMediaBaseUrl = 'http://127.0.0.1:8080';

  /// Optional compile-time overrides (preferred for staging/production).
  static const String _defineApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );
  static const String _defineMediaBaseUrl = String.fromEnvironment(
    'MEDIA_BASE_URL',
    defaultValue: '',
  );

  /// Android emulator loopback alias for host machine.
  static const String androidEmulatorApiBaseUrl =
      'http://10.0.2.2:8080/api/v1';
  static const String androidEmulatorMediaBaseUrl = 'http://10.0.2.2:8080';

  /// REST API base (includes /api/v1).
  static String get apiBaseUrl {
    if (_defineApiBaseUrl.isNotEmpty) return _defineApiBaseUrl;
    return _defaultApiBaseUrl;
  }

  /// Origin used to resolve relative media paths if the API returns them.
  static String get mediaBaseUrl {
    if (_defineMediaBaseUrl.isNotEmpty) return _defineMediaBaseUrl;
    return _defaultMediaBaseUrl;
  }

  /// Convenience helper when targeting Android emulator without dart-define.
  static String apiBaseUrlForAndroidEmulator() => androidEmulatorApiBaseUrl;

  static String mediaBaseUrlForAndroidEmulator() =>
      androidEmulatorMediaBaseUrl;

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
