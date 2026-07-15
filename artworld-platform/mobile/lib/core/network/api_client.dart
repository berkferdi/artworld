import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/app_config.dart';
import 'api_exception.dart';

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient();
});

/// Dio-based HTTP client with Turkish error messages and optional debug logs.
class ApiClient {
  ApiClient({Dio? dio}) : _dio = dio ?? _createDio();

  final Dio _dio;

  static Dio _createDio() {
    final dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.apiBaseUrl,
        connectTimeout: AppConfig.connectTimeout,
        receiveTimeout: AppConfig.receiveTimeout,
        sendTimeout: AppConfig.sendTimeout,
        // Do not set Content-Type globally: PHP built-in server can reject GET
        // requests that advertise application/json with HTTP 400 empty body.
        headers: const {
          'Accept': 'application/json',
        },
        validateStatus: (status) => status != null && status < 500,
      ),
    );

    if (AppConfig.isDebug) {
      dio.interceptors.add(
        LogInterceptor(
          requestBody: true,
          responseBody: true,
          error: true,
          logPrint: (obj) => debugPrint(obj.toString()),
        ),
      );
    }

    dio.interceptors.add(
      InterceptorsWrapper(
        onError: (error, handler) async {
          final opts = error.requestOptions;
          final retried = opts.extra['retried'] == true;
          final retriable = error.type == DioExceptionType.connectionTimeout ||
              error.type == DioExceptionType.receiveTimeout ||
              error.type == DioExceptionType.connectionError;

          if (!retried && retriable) {
            opts.extra['retried'] = true;
            try {
              final response = await dio.fetch(opts);
              return handler.resolve(response);
            } catch (_) {
              // fall through
            }
          }
          return handler.next(error);
        },
      ),
    );

    return dio;
  }

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.get<dynamic>(
        path,
        queryParameters: queryParameters,
      );
      return _unwrap(response);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    try {
      final response = await _dio.post<dynamic>(
        path,
        data: data,
        options: Options(
          contentType: Headers.jsonContentType,
          headers: const {'Accept': 'application/json'},
        ),
      );
      return _unwrap(response);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Map<String, dynamic> _unwrap(Response<dynamic> response) {
    final status = response.statusCode ?? 0;
    final body = response.data;

    if (body is! Map) {
      throw ApiException(
        message: 'Sunucudan geçersiz yanıt alındı.',
        statusCode: status,
      );
    }

    final map = Map<String, dynamic>.from(body);
    final success = map['success'] == true;

    if (status >= 400 || !success) {
      throw ApiException(
        message: (map['message'] as String?)?.trim().isNotEmpty == true
            ? map['message'] as String
            : _statusMessage(status),
        statusCode: status,
        errors: map['errors'] is Map
            ? Map<String, dynamic>.from(map['errors'] as Map)
            : null,
      );
    }

    return map;
  }

  ApiException _mapDio(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return ApiException(
          message: 'Bağlantı zaman aşımına uğradı. Lütfen tekrar deneyin.',
          statusCode: e.response?.statusCode,
        );
      case DioExceptionType.connectionError:
        return ApiException(
          message: 'İnternet bağlantısı yok veya sunucuya ulaşılamıyor.',
          statusCode: e.response?.statusCode,
        );
      case DioExceptionType.badResponse:
        final data = e.response?.data;
        if (data is Map && data['message'] is String) {
          return ApiException(
            message: data['message'] as String,
            statusCode: e.response?.statusCode,
            errors: data['errors'] is Map
                ? Map<String, dynamic>.from(data['errors'] as Map)
                : null,
          );
        }
        return ApiException(
          message: _statusMessage(e.response?.statusCode ?? 0),
          statusCode: e.response?.statusCode,
        );
      case DioExceptionType.cancel:
        return ApiException(message: 'İstek iptal edildi.');
      default:
        return ApiException(
          message: 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.',
          statusCode: e.response?.statusCode,
        );
    }
  }

  String _statusMessage(int status) {
    switch (status) {
      case 400:
        return 'Geçersiz istek.';
      case 401:
        return 'Yetkilendirme gerekli.';
      case 403:
        return 'Bu işlem için yetkiniz yok.';
      case 404:
        return 'İçerik bulunamadı.';
      case 422:
        return 'Doğrulama hatası.';
      case 429:
        return 'Çok fazla istek gönderildi. Lütfen biraz bekleyin.';
      case 500:
      case 502:
      case 503:
        return 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.';
      default:
        return 'Bir hata oluştu (kod: $status).';
    }
  }

  /// Absolute media URL helper when API returns a relative path.
  static String resolveMediaUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    final base = AppConfig.mediaBaseUrl.endsWith('/')
        ? AppConfig.mediaBaseUrl.substring(0, AppConfig.mediaBaseUrl.length - 1)
        : AppConfig.mediaBaseUrl;
    final p = path.startsWith('/') ? path : '/$path';
    return '$base$p';
  }
}
