import 'package:flutter/material.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../widgets/common.dart';
import '../widgets/solution_card.dart';

/// After "إرسال": score, correct answers, explanations and passage links.
class ResultScreen extends StatefulWidget {
  const ResultScreen({super.key, required Attempt this.attempt}) : attemptId = null;
  const ResultScreen.load(int this.attemptId, {super.key}) : attempt = null;

  final Attempt? attempt;
  final int? attemptId;

  @override
  State<ResultScreen> createState() => _ResultScreenState();
}

class _ResultScreenState extends State<ResultScreen> {
  late final Future<Attempt> _future = widget.attempt != null
      ? Future.value(widget.attempt!)
      : ApiClient.instance.get('/attempts/${widget.attemptId}').then((j) => Attempt.fromJson(j['data']));
  bool _wrongOnly = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('النتيجة')),
      body: AsyncView<Attempt>(
        future: _future,
        builder: (context, attempt) {
          final score = attempt.score ?? 0;
          final color = score >= 75 ? Colors.green : (score >= 50 ? Colors.orange : Colors.red);
          final items = attempt.questions.indexed.where((e) => !_wrongOnly || e.$2.isCorrect != true).toList();

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(children: [
                    SizedBox(
                      width: 120,
                      height: 120,
                      child: Stack(fit: StackFit.expand, children: [
                        CircularProgressIndicator(value: score / 100, strokeWidth: 10, color: color, backgroundColor: color.withValues(alpha: 0.15)),
                        Center(child: Text('${score.round()}%', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: color))),
                      ]),
                    ),
                    const SizedBox(height: 14),
                    Text('${attempt.correctCount ?? 0} إجابة صحيحة من ${attempt.total}', style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
                    Text(
                      score >= 90 ? 'ممتاز! أداء رائع 🌟' : score >= 75 ? 'جيد جداً، استمر 👏' : score >= 50 ? 'جيد، راجع الأسئلة الخاطئة' : 'تحتاج إلى مراجعة الملزمة',
                      style: TextStyle(color: Colors.grey.shade700),
                    ),
                    const SizedBox(height: 6),
                    Text('${modeLabels[attempt.mode] ?? ''}${attempt.difficulty != null ? ' — ${difficultyLabels[attempt.difficulty]}' : ''}',
                        style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
                  ]),
                ),
              ),
              const SizedBox(height: 8),
              Row(children: [
                ChoiceChip(label: const Text('كل الأسئلة'), selected: !_wrongOnly, onSelected: (_) => setState(() => _wrongOnly = false)),
                const SizedBox(width: 8),
                ChoiceChip(label: const Text('الخاطئة فقط'), selected: _wrongOnly, onSelected: (_) => setState(() => _wrongOnly = true)),
              ]),
              const SizedBox(height: 12),
              for (final (i, q) in items) SolutionCard(number: i + 1, question: q),
            ],
          );
        },
      ),
    );
  }
}
