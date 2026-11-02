import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'core/protection.dart';
import 'core/session.dart';
import 'core/theme.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Session.instance.init();
  await ContentProtection.enable();
  runApp(const BooklitApp());
}

class BooklitApp extends StatelessWidget {
  const BooklitApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Session.instance,
      builder: (context, _) => MaterialApp(
        title: 'بوكلت',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        // Allow drag-scrolling with a mouse on web/desktop too.
        scrollBehavior: const MaterialScrollBehavior().copyWith(dragDevices: PointerDeviceKind.values.toSet()),
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (context, child) => ProtectedContent(child: child ?? const SizedBox()),
        home: Session.instance.isLoggedIn ? const HomeScreen() : const LoginScreen(),
      ),
    );
  }
}
