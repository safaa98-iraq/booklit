import 'package:flutter/material.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../core/repository.dart';
import '../widgets/block_view.dart';
import '../widgets/common.dart';
import 'review_screen.dart';
import 'solve_screen.dart';

/// Booklet reader with topic navigation and the 4 bottom buttons.
/// When [highlight] is given, scrolls to that passage and highlights it.
class ReaderScreen extends StatefulWidget {
  const ReaderScreen({super.key, required this.bookletId, this.highlight});

  final int bookletId;
  final PassageAnchor? highlight;

  @override
  State<ReaderScreen> createState() => _ReaderScreenState();
}

class _ReaderScreenState extends State<ReaderScreen> {
  late Future<Booklet> _future = Repository.instance.booklet(widget.bookletId);
  final _keys = <String, GlobalKey>{};
  bool _scrolled = false;

  GlobalKey _key(String id) => _keys.putIfAbsent(id, GlobalKey.new);

  void _scrollTo(String id) {
    final ctx = _keys[id]?.currentContext;
    if (ctx != null) Scrollable.ensureVisible(ctx, duration: const Duration(milliseconds: 450), alignment: 0.15, curve: Curves.easeInOut);
  }

  bool get _highlightOnly => widget.highlight != null;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Booklet>(
      future: _future,
      builder: (context, snap) {
        final booklet = snap.data;
        return Scaffold(
          appBar: AppBar(
            title: Text(booklet?.title ?? 'الملزمة', overflow: TextOverflow.ellipsis),
            actions: [
              if (booklet != null)
                Builder(builder: (context) => IconButton(icon: const Icon(Icons.toc), tooltip: 'المواضيع', onPressed: () => Scaffold.of(context).openEndDrawer())),
            ],
          ),
          endDrawer: booklet == null ? null : _topicsDrawer(booklet),
          bottomNavigationBar: booklet == null || _highlightOnly ? null : _BottomActions(booklet: booklet),
          body: snap.connectionState != ConnectionState.done
              ? const Center(child: CircularProgressIndicator())
              : snap.hasError
                  ? AsyncView<Booklet>(future: _future, builder: (_, _) => const SizedBox(), onRetry: () => setState(() => _future = Repository.instance.booklet(widget.bookletId)))
                  : _content(booklet!),
        );
      },
    );
  }

  Widget _content(Booklet booklet) {
    final h = widget.highlight;
    if (h != null && !_scrolled) {
      _scrolled = true;
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollTo(h.blockId));
    }

    return Column(children: [
      if (h != null)
        MaterialBanner(
          backgroundColor: const Color(0xFFFFF7D6),
          leading: const Icon(Icons.highlight),
          content: const Text('المقطع المظلل هو مصدر إجابة السؤال.'),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('رجوع للنتيجة'))],
        ),
      Expanded(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(18, 12, 18, 40),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 760),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                for (final topic in booklet.topics) ...[
                  Container(
                    key: _key('topic-${topic.id}'),
                    margin: const EdgeInsets.only(top: 16, bottom: 4),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(color: Theme.of(context).colorScheme.primaryContainer, borderRadius: BorderRadius.circular(12)),
                    child: Text(topic.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                  ),
                  for (final block in topic.blocks)
                    KeyedSubtree(
                      key: _key(block.id),
                      child: BlockView(block: block, highlight: h != null && h.blockId == block.id ? (h.start, h.end) : null),
                    ),
                ],
              ]),
            ),
          ),
        ),
      ),
    ]);
  }

  Widget _topicsDrawer(Booklet booklet) {
    return Drawer(
      child: SafeArea(
        child: ListView(children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text('المواضيع', style: Theme.of(context).textTheme.titleLarge!.copyWith(fontWeight: FontWeight.w800)),
          ),
          for (final (i, topic) in booklet.topics.indexed)
            ListTile(
              leading: CircleAvatar(radius: 14, child: Text('${i + 1}', style: const TextStyle(fontSize: 12))),
              title: Text(topic.title),
              onTap: () {
                Navigator.pop(context);
                Future.delayed(const Duration(milliseconds: 250), () => _scrollTo('topic-${topic.id}'));
              },
            ),
        ]),
      ),
    );
  }
}

class _BottomActions extends StatelessWidget {
  const _BottomActions({required this.booklet});
  final Booklet booklet;

  @override
  Widget build(BuildContext context) {
    final fourth = booklet.fourthButton;
    return SafeArea(
      child: Container(
        decoration: BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Colors.grey.shade200))),
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
        child: Row(children: [
          _ActionButton(icon: Icons.account_balance, label: 'MCQ وزاري', enabled: booklet.ministerial.enabled, onTap: () => _mcq(context, 'ministerial', booklet.ministerial)),
          _ActionButton(icon: Icons.school, label: 'MCQ الأستاذ', enabled: booklet.teacher.enabled, onTap: () => _mcq(context, 'teacher', booklet.teacher)),
          _ActionButton(icon: Icons.timer_outlined, label: 'وضع الاختبار', enabled: booklet.quizEnabled, onTap: () => _quiz(context)),
          _ActionButton(
            icon: switch (fourth.icon) { 'video' => Icons.play_circle_outline, 'bookmark' => Icons.bookmark_border, 'chat' => Icons.chat_bubble_outline, 'download' => Icons.download, 'info' => Icons.info_outline, _ => Icons.star_border },
            label: fourth.label,
            enabled: fourth.enabled,
            onTap: () => fourth.action == 'url' && (fourth.value ?? '').isNotEmpty
                ? openUrl(context, fourth.value!)
                : ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('هذه الميزة قادمة قريباً.'))),
          ),
        ]),
      ),
    );
  }

  /// MCQ modes: solve, or "أظهر الحل" to review without solving.
  void _mcq(BuildContext context, String source, ModeAction action) {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (sheet) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text(modeLabels[source]!, textAlign: TextAlign.center, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
            Text('${action.count} سؤال', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)),
            const SizedBox(height: 18),
            FilledButton.icon(
              icon: const Icon(Icons.edit_note),
              label: const Text('ابدأ الحل'),
              onPressed: () {
                Navigator.pop(sheet);
                _start(context, source);
              },
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(50)),
              icon: const Icon(Icons.visibility_outlined),
              label: const Text('أظهر الحل'),
              onPressed: () {
                Navigator.pop(sheet);
                Navigator.push(context, MaterialPageRoute(builder: (_) => ReviewScreen(booklet: booklet, source: source)));
              },
            ),
          ]),
        ),
      ),
    );
  }

  /// Quiz mode: pick a difficulty first; the number of questions is set by the editor.
  void _quiz(BuildContext context) {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (sheet) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const Text('وضع الاختبار', textAlign: TextAlign.center, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
            Text('اختر مستوى الصعوبة — ${booklet.quizSize} أسئلة عشوائية', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)),
            const SizedBox(height: 14),
            for (final d in ['easy', 'medium', 'hard'])
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(52)),
                  onPressed: (booklet.quizByDifficulty[d] ?? 0) == 0
                      ? null
                      : () {
                          Navigator.pop(sheet);
                          _start(context, 'quiz', difficulty: d);
                        },
                  child: Row(children: [
                    DifficultyChip(d),
                    const Spacer(),
                    Text('${booklet.quizByDifficulty[d] ?? 0} سؤال متاح', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                  ]),
                ),
              ),
          ]),
        ),
      ),
    );
  }

  Future<void> _start(BuildContext context, String mode, {String? difficulty}) async {
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final attempt = await Repository.instance.startAttempt(bookletId: booklet.id, mode: mode, difficulty: difficulty);
      if (!context.mounted) return;
      Navigator.pop(context);
      Navigator.push(context, MaterialPageRoute(builder: (_) => SolveScreen(attempt: attempt)));
    } on ApiException catch (e) {
      if (!context.mounted) return;
      Navigator.pop(context);
      e.isLocked ? showLockedDialog(context, message: e.message) : showError(context, e);
    }
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({required this.icon, required this.label, required this.enabled, required this.onTap});
  final IconData icon;
  final String label;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = enabled ? Theme.of(context).colorScheme.primary : Colors.grey.shade400;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: enabled ? onTap : null,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(icon, color: color),
            const SizedBox(height: 2),
            Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: color)),
          ]),
        ),
      ),
    );
  }
}
