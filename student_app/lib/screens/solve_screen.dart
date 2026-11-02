import 'package:flutter/material.dart';

import '../core/models.dart';
import '../core/repository.dart';
import '../widgets/common.dart';
import 'result_screen.dart';

/// Solving flow: questions and options arrive already shuffled by the server.
/// Answers can be changed freely until "إرسال".
class SolveScreen extends StatefulWidget {
  const SolveScreen({super.key, required this.attempt});
  final Attempt attempt;

  @override
  State<SolveScreen> createState() => _SolveScreenState();
}

class _SolveScreenState extends State<SolveScreen> {
  final _pages = PageController();
  late final Map<int, int?> _answers = {for (final q in widget.attempt.questions) q.id: q.selectedOptionId};
  int _index = 0;
  bool _submitting = false;

  List<QuestionItem> get _questions => widget.attempt.questions;
  int get _answered => _answers.values.where((v) => v != null).length;

  void _select(QuestionItem q, int optionId) {
    setState(() => _answers[q.id] = optionId);
    // Save in the background so the attempt can be resumed; ignore failures (submit sends everything).
    Repository.instance.saveAnswer(widget.attempt.id, q.id, optionId).catchError((_) {});
  }

  void _go(int i) => _pages.animateToPage(i, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);

  Future<void> _submit() async {
    final unanswered = _questions.length - _answered;
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('إرسال الإجابات؟'),
        content: Text(unanswered == 0 ? 'أجبت على جميع الأسئلة.' : 'لم تجب على $unanswered من ${_questions.length} سؤال. ستُحسب الأسئلة غير المجابة خاطئة.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('مراجعة')),
          FilledButton(style: FilledButton.styleFrom(minimumSize: const Size(100, 44)), onPressed: () => Navigator.pop(context, true), child: const Text('إرسال')),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => _submitting = true);
    try {
      final result = await Repository.instance.submit(widget.attempt.id, _answers);
      if (!mounted) return;
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => ResultScreen(attempt: result)));
    } catch (e) {
      if (mounted) {
        setState(() => _submitting = false);
        showError(context, e);
      }
    }
  }

  void _showNavigator() {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (sheet) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
        child: Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final (i, q) in _questions.indexed)
              InkWell(
                onTap: () {
                  Navigator.pop(sheet);
                  _go(i);
                },
                child: CircleAvatar(
                  radius: 20,
                  backgroundColor: i == _index ? Theme.of(context).colorScheme.primary : (_answers[q.id] != null ? Colors.teal.shade100 : Colors.grey.shade200),
                  child: Text('${i + 1}', style: TextStyle(color: i == _index ? Colors.white : Colors.black87, fontWeight: FontWeight.w700)),
                ),
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final total = _questions.length;
    final isLast = _index == total - 1;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        final leave = await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('الخروج من الحل؟'),
            content: const Text('إجاباتك محفوظة، لكن لن تُحتسب النتيجة قبل الإرسال.'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('متابعة الحل')),
              TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('خروج')),
            ],
          ),
        );
        if (leave == true && context.mounted) Navigator.pop(context);
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(modeLabels[widget.attempt.mode] ?? 'الأسئلة'),
          actions: [TextButton(onPressed: _submitting ? null : _submit, child: const Text('إرسال'))],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(4),
            child: LinearProgressIndicator(value: total == 0 ? 0 : _answered / total),
          ),
        ),
        body: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Row(children: [
              Text('سؤال ${_index + 1} من $total', style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(width: 8),
              DifficultyChip(_questions[_index].difficulty),
              const Spacer(),
              TextButton.icon(onPressed: _showNavigator, icon: const Icon(Icons.grid_view, size: 18), label: Text('$_answered/$total')),
            ]),
          ),
          Expanded(
            child: PageView.builder(
              controller: _pages,
              itemCount: total,
              onPageChanged: (i) => setState(() => _index = i),
              itemBuilder: (context, i) => _QuestionPage(question: _questions[i], selected: _answers[_questions[i].id], onSelect: (id) => _select(_questions[i], id)),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(children: [
                Expanded(
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(50)),
                    onPressed: _index == 0 ? null : () => _go(_index - 1),
                    child: const Text('السابق'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    onPressed: _submitting ? null : (isLast ? _submit : () => _go(_index + 1)),
                    child: _submitting
                        ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                        : Text(isLast ? 'إرسال' : 'التالي'),
                  ),
                ),
              ]),
            ),
          ),
        ]),
      ),
    );
  }
}

class _QuestionPage extends StatelessWidget {
  const _QuestionPage({required this.question, required this.selected, required this.onSelect});
  final QuestionItem question;
  final int? selected;
  final ValueChanged<int> onSelect;

  @override
  Widget build(BuildContext context) {
    final primary = Theme.of(context).colorScheme.primary;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Text(question.text, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700, height: 1.7)),
          ),
        ),
        const SizedBox(height: 8),
        for (final (i, option) in question.options.indexed)
          Padding(
            padding: const EdgeInsets.only(top: 10),
            child: Material(
              color: selected == option.id ? primary.withValues(alpha: 0.1) : Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
                side: BorderSide(color: selected == option.id ? primary : Colors.grey.shade300, width: selected == option.id ? 2 : 1),
              ),
              child: InkWell(
                borderRadius: BorderRadius.circular(14),
                onTap: () => onSelect(option.id),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  child: Row(children: [
                    CircleAvatar(
                      radius: 15,
                      backgroundColor: selected == option.id ? primary : Colors.grey.shade200,
                      child: Text('أبجدهوز'[i], style: TextStyle(color: selected == option.id ? Colors.white : Colors.black87, fontWeight: FontWeight.w700)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(child: Text(option.text, style: const TextStyle(fontSize: 16, height: 1.6))),
                  ]),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
