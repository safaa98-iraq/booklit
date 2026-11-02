import 'dart:convert';

import 'package:http/http.dart' as http;

import 'config.dart';

/// Error returned by the API, with the server's Arabic message and machine code.
class ApiException implements Exception {
  ApiException(this.status, this.message, {this.code, this.data = const {}});

  final int status;
  final String message;
  final String? code;
  final Map<String, dynamic> data;

  bool get isLocked => code == 'subject_locked';
  bool get isDeviceMismatch => code == 'device_mismatch';
  bool get isUnauthorized => status == 401;

  @override
  String toString() => message;
}

/// Thin JSON client: adds the bearer token and the bound device ID to every request.
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  String? token;
  String deviceId = '';

  /// Called when the server says the session is no longer valid (401 / device mismatch).
  void Function(ApiException error)? onSessionInvalid;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Device-Id': deviceId,
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Uri _uri(String path, [Map<String, String>? query]) =>
      Uri.parse('${AppConfig.apiBaseUrl}$path').replace(queryParameters: query);

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) =>
      _send(() => http.get(_uri(path, query), headers: _headers));

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) =>
      _send(() => http.post(_uri(path), headers: _headers, body: jsonEncode(body ?? {})));

  Future<Map<String, dynamic>> put(String path, [Map<String, dynamic>? body]) =>
      _send(() => http.put(_uri(path), headers: _headers, body: jsonEncode(body ?? {})));

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    http.Response response;
    try {
      response = await request().timeout(const Duration(seconds: 90));
    } catch (_) {
      throw ApiException(0, 'تعذر الاتصال بالخادم. تحقق من اتصالك بالإنترنت وحاول مجدداً.');
    }

    Map<String, dynamic> json = {};
    if (response.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(utf8.decode(response.bodyBytes));
        if (decoded is Map<String, dynamic>) json = decoded;
      } catch (_) {}
    }

    if (response.statusCode >= 200 && response.statusCode < 300) return json;

    var message = (json['message'] as String?) ?? 'حدث خطأ غير متوقع (${response.statusCode}).';
    final errors = json['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) message = first.first.toString();
    }

    final error = ApiException(response.statusCode, message, code: json['code'] as String?, data: json);
    if (error.isUnauthorized || error.isDeviceMismatch) onSessionInvalid?.call(error);
    throw error;
  }
}
