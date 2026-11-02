import 'package:flutter/material.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../core/repository.dart';
import '../core/session.dart';
import '../widgets/common.dart';
import 'result_screen.dart';

Future<void> showAccountSheet(BuildContext context) {
  return showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => const _AccountSheet(),
  );
}

class _AccountSheet extends StatelessWidget {
  const _AccountSheet();

  @override
  Widget build(BuildContext context) {
    final user = Session.instance.user!;
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.7,
      maxChildSize: 0.95,
      builder: (context, controller) => ListView(
        controller: controller,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
        children: [
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: CircleAvatar(radius: 24, child: Text(user.name.characters.first)),
            title: Text(user.name, style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text(user.phone, textDirection: TextDirection.ltr, textAlign: TextAlign.end),
          ),
          const Divider(),
          FutureBuilder<Map<String, dynamic>>(
            future: ApiClient.instance.get('/me'),
            builder: (context, snap) {
              final subs = (snap.data?['data']?['subscriptions'] as List? ?? []).cast<Map<String, dynamic>>();
              return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('اشتراكاتي', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                const SizedBox(height: 6),
                if (snap.connectionState != ConnectionState.done)
                  const LinearProgressIndicator()
                else if (subs.isEmpty)
                  Text('لا توجد اشتراكات فعّالة. المواد المجانية متاحة لك دائماً.', style: TextStyle(color: Colors.grey.shade600))
                else
                  ...subs.map((s) => ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.verified, color: Colors.green),
                        title: Text(s['stage']?['name'] ?? s['subject']?['name'] ?? ''),
                        subtitle: Text('ينتهي في ${(s['expires_at'] ?? '').toString().split('T').first}'),
                      )),
              ]);
            },
          ),
          const Divider(height: 28),
          const Text('آخر المحاولات', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          FutureBuilder<List<Attempt>>(
            future: Repository.instance.history(),
            builder: (context, snap) {
              if (snap.connectionState != ConnectionState.done) return const LinearProgressIndicator();
              final items = (snap.data ?? []).where((a) => a.isSubmitted).take(10).toList();
              if (items.isEmpty) return Text('لم تحل أي اختبار بعد.', style: TextStyle(color: Colors.grey.shade600));
              return Column(
                children: items
                    .map((a) => ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: CircleAvatar(
                            backgroundColor: (a.score ?? 0) >= 50 ? Colors.green.shade50 : Colors.red.shade50,
                            child: Text('${(a.score ?? 0).round()}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                          ),
                          title: Text(modeLabels[a.mode] ?? a.mode),
                          subtitle: Text('${a.correctCount ?? 0} / ${a.total} إجابة صحيحة'),
                          trailing: const Icon(Icons.chevron_left),
                          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ResultScreen.load(a.id))),
                        ))
                    .toList(),
              );
            },
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            icon: const Icon(Icons.logout),
            label: const Text('تسجيل الخروج'),
            onPressed: () async {
              Navigator.pop(context);
              await Session.instance.logout();
            },
          ),
          const SizedBox(height: 8),
          Text('الحساب مرتبط بهذا الجهاز. لتغيير الجهاز تواصل مع الإدارة.',
              textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
          if (Session.instance.settings?.telegramUrl.isNotEmpty == true)
            TextButton.icon(
              icon: const Icon(Icons.send),
              label: const Text('تواصل مع الإدارة'),
              onPressed: () => openUrl(context, Session.instance.settings!.telegramUrl),
            ),
        ],
      ),
    );
  }
}
