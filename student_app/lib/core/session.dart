import 'dart:convert';
import 'dart:math';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';
import 'models.dart';
import 'repository.dart';

/// Holds the signed-in student, the bound device ID and app settings.
class Session extends ChangeNotifier {
  Session._();
  static final Session instance = Session._();

  static const _kToken = 'token', _kUser = 'user', _kDevice = 'device_id';

  AppUser? user;
  AppSettings? settings;
  String? sessionMessage; // shown on the login screen after a forced logout

  bool get isLoggedIn => user != null && ApiClient.instance.token != null;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    var device = prefs.getString(_kDevice);
    if (device == null) {
      device = _newDeviceId();
      await prefs.setString(_kDevice, device);
    }
    ApiClient.instance
      ..deviceId = device
      ..token = prefs.getString(_kToken)
      ..onSessionInvalid = (error) => _forceLogout(error.message);

    final rawUser = prefs.getString(_kUser);
    if (rawUser != null) user = AppUser.fromJson(jsonDecode(rawUser));

    try {
      settings = await Repository.instance.settings();
    } catch (_) {}
  }

  String get platform => kIsWeb
      ? 'web'
      : defaultTargetPlatform == TargetPlatform.iOS
          ? 'ios'
          : 'android';

  String get deviceName => kIsWeb ? 'متصفح الويب' : (platform == 'ios' ? 'iPhone' : 'Android');

  Future<void> login(String phone, String password) => _authenticate('/auth/login', {'phone': phone, 'password': password});

  Future<void> register(String name, String phone, String password) => _authenticate('/auth/register', {
        'name': name,
        'phone': phone,
        'password': password,
        'password_confirmation': password,
      });

  Future<void> _authenticate(String path, Map<String, dynamic> body) async {
    final json = await ApiClient.instance.post(path, {
      ...body,
      'device_id': ApiClient.instance.deviceId,
      'device_name': deviceName,
      'platform': platform,
    });
    ApiClient.instance.token = json['token'];
    user = AppUser.fromJson(json['user']);
    sessionMessage = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kToken, json['token']);
    await prefs.setString(_kUser, jsonEncode(user!.toJson()));
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.post('/auth/logout');
    } catch (_) {}
    await _clear();
  }

  Future<void> _forceLogout(String message) async {
    if (!isLoggedIn) return;
    sessionMessage = message;
    await _clear();
  }

  Future<void> _clear() async {
    ApiClient.instance.token = null;
    user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kToken);
    await prefs.remove(_kUser);
    notifyListeners();
  }

  static String _newDeviceId() {
    final r = Random.secure();
    final bytes = List<int>.generate(16, (_) => r.nextInt(256));
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }
}
