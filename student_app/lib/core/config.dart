import 'package:flutter/foundation.dart';

/// API base URL.
/// - Web build served by Laravel (`/app/`): same origin → `<origin>/api/v1`.
/// - Mobile: pass `--dart-define=API_BASE_URL=https://…/api/v1` or use the default below.
class AppConfig {
  static const _fromEnv = String.fromEnvironment('API_BASE_URL');
  static const _defaultRemote = 'https://booklit-6xmk.onrender.com/api/v1';

  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) return _fromEnv;
    if (kIsWeb) return '${Uri.base.origin}/api/v1';
    return _defaultRemote;
  }
}
