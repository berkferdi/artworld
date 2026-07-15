import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

import '../config/app_config.dart';
import '../network/api_client.dart';

final pushNotificationServiceProvider = Provider<PushNotificationService>((ref) {
  return PushNotificationService(ref.watch(apiClientProvider));
});

/// Initializes Firebase Messaging when configured; otherwise fails silently.
class PushNotificationService {
  PushNotificationService(this._api);

  final ApiClient _api;
  bool _initialized = false;

  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    try {
      await Firebase.initializeApp();
    } on FirebaseException catch (e) {
      debugPrint('FCM: Firebase yapılandırması yok, atlanıyor (${e.code}).');
      return;
    } catch (e) {
      debugPrint('FCM: Firebase başlatılamadı, atlanıyor: $e');
      return;
    }

    try {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      final token = await messaging.getToken();
      if (token != null && token.isNotEmpty) {
        await _registerToken(token);
      }

      FirebaseMessaging.instance.onTokenRefresh.listen(_registerToken);
    } catch (e) {
      debugPrint('FCM: token alınamadı: $e');
    }
  }

  Future<void> _registerToken(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      var uuid = prefs.getString(AppConfig.deviceUuidKey);
      if (uuid == null || uuid.isEmpty) {
        uuid = const Uuid().v4();
        await prefs.setString(AppConfig.deviceUuidKey, uuid);
      }

      final info = await PackageInfo.fromPlatform();
      final platform = Platform.isIOS ? 'ios' : 'android';

      await _api.post(
        '/devices/register',
        data: {
          'fcm_token': token,
          'platform': platform,
          'device_uuid': uuid,
          'app_version': info.version,
        },
      );
    } catch (e) {
      debugPrint('FCM: cihaz kaydı başarısız: $e');
    }
  }
}
