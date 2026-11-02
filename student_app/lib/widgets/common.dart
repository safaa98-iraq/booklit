import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../core/session.dart';

void showError(BuildContext context, Object error) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error is ApiException ? error.message : 'حدث خطأ غير متوقع.')));
}

Future<void> openUrl(BuildContext context, String url) async {
  final uri = Uri.tryParse(url);
  if (uri == null || !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
    if (context.mounted) showError(context, ApiException(0, 'تعذر فتح الرابط.'));
  }
}

/// Locked (ministerial) subject: message + button to the admin's Telegram.
Future<void> showLockedDialog(BuildContext context, {String? message}) {
  final settings = Session.instance.settings;
  return showDialog(
    context: context,
    builder: (context) => AlertDialog(
      icon: const Icon(Icons.lock_outline, size: 40),
      title: const Text('المادة مقفلة'),
      content: Text(message ?? settings?.lockedMessage ?? 'هذه المادة متاحة للمشتركين فقط.', textAlign: TextAlign.center),
      actionsAlignment: MainAxisAlignment.center,
      actions: [
        if ((settings?.telegramUrl ?? '').isNotEmpty)
          FilledButton.icon(
            icon: const Icon(Icons.send),
            label: Text(settings?.lockedButton ?? 'تواصل عبر تيليجرام'),
            onPressed: () => openUrl(context, settings!.telegramUrl),
          ),
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('إغلاق')),
      ],
    ),
  );
}

/// Loading / error / content wrapper for a Future.
class AsyncView<T> extends StatelessWidget {
  const AsyncView({super.key, required this.future, required this.builder, this.onRetry});
  final Future<T> future;
  final Widget Function(BuildContext, T) builder;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snap.hasError) {
          final error = snap.error;
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(error is ApiException && error.isLocked ? Icons.lock_outline : Icons.cloud_off, size: 48, color: Colors.grey),
                const SizedBox(height: 12),
                Text(error is ApiException ? error.message : 'حدث خطأ غير متوقع.', textAlign: TextAlign.center),
                if (onRetry != null) ...[
                  const SizedBox(height: 12),
                  OutlinedButton(onPressed: onRetry, child: const Text('إعادة المحاولة')),
                ],
              ]),
            ),
          );
        }
        return builder(context, snap.data as T);
      },
    );
  }
}

class DifficultyChip extends StatelessWidget {
  const DifficultyChip(this.difficulty, {super.key});
  final String? difficulty;

  @override
  Widget build(BuildContext context) {
    if (difficulty == null) return const SizedBox.shrink();
    final color = switch (difficulty) { 'easy' => Colors.green, 'hard' => Colors.red, _ => Colors.orange };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(difficultyLabels[difficulty] ?? difficulty!, style: TextStyle(color: color.shade800, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}
