import 'dart:ui';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:screen_protector/screen_protector.dart';

import 'session.dart';

/// Content protection:
/// - Android: FLAG_SECURE (screenshots and recordings show a black screen).
/// - iOS: screenshot prevention + blur while the screen is being recorded/mirrored.
/// - Web: dynamic watermark with the student's name and phone (see [ProtectedContent]).
class ContentProtection {
  static final ValueNotifier<bool> capturing = ValueNotifier(false);

  static Future<void> enable() async {
    if (kIsWeb) return;
    try {
      await ScreenProtector.preventScreenshotOn();
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        await ScreenProtector.protectDataLeakageWithBlur();
        capturing.value = await ScreenProtector.isRecording();
        ScreenProtector.addListener(null, (recording) => capturing.value = recording);
      }
    } catch (_) {
      // Plugin unavailable (e.g. tests) — continue without native protection.
    }
  }
}

class ProtectedContent extends StatelessWidget {
  const ProtectedContent({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<bool>(
      valueListenable: ContentProtection.capturing,
      builder: (context, capturing, _) => Stack(
        children: [
          child,
          if (kIsWeb && Session.instance.user != null) Positioned.fill(child: Watermark(text: Session.instance.user!.watermark)),
          if (capturing)
            Positioned.fill(
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 30, sigmaY: 30),
                child: Container(
                  color: Colors.black.withValues(alpha: 0.6),
                  alignment: Alignment.center,
                  child: const Text('تم إخفاء المحتوى أثناء تسجيل الشاشة', style: TextStyle(color: Colors.white, fontSize: 18)),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Repeated, rotated, semi-transparent text across the whole screen.
class Watermark extends StatelessWidget {
  const Watermark({super.key, required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(child: CustomPaint(painter: _WatermarkPainter(text)));
  }
}

class _WatermarkPainter extends CustomPainter {
  _WatermarkPainter(this.text);
  final String text;

  @override
  void paint(Canvas canvas, Size size) {
    final painter = TextPainter(
      text: TextSpan(text: text, style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: Colors.black.withValues(alpha: 0.07))),
      textDirection: TextDirection.rtl,
    )..layout();
    const stepX = 260.0, stepY = 130.0;
    canvas.save();
    canvas.rotate(-0.4);
    var row = 0;
    for (var y = -size.height; y < size.height * 2; y += stepY, row++) {
      final shift = row.isEven ? 0.0 : stepX / 2; // stagger alternate rows
      for (var x = -size.width; x < size.width * 2; x += stepX) {
        painter.paint(canvas, Offset(x + shift, y));
      }
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _WatermarkPainter old) => old.text != text;
}
