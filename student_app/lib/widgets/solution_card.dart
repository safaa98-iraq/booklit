import 'package:flutter/material.dart';

import '../core/models.dart';
import '../screens/reader_screen.dart';
import 'common.dart';

/// A question with its correct answer, the student's choice (if any),
/// the explanation, and a link to the highlighted passage in the booklet.
class SolutionCard extends StatelessWidget {
  const SolutionCard({super.key, required this.number, required this.question, this.showStudentChoice = true});

  final int number;
  final QuestionItem question;
  final bool showStudentChoice;

  @override
  Widget build(BuildContext context) {
    final correctId = question.correctOptionId ?? question.options.where((o) => o.isCorrect == true).firstOrNull?.id;
    final chosen = question.selectedOptionId;
    final status = !showStudentChoice ? null : (question.isCorrect == true ? true : false);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            CircleAvatar(
              radius: 14,
              backgroundColor: status == null ? Colors.grey.shade200 : (status ? Colors.green.shade100 : Colors.red.shade100),
              child: status == null
                  ? Text('$number', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700))
                  : Icon(status ? Icons.check : Icons.close, size: 16, color: status ? Colors.green.shade800 : Colors.red.shade800),
            ),
            const SizedBox(width: 8),
            DifficultyChip(question.difficulty),
            const Spacer(),
            if (question.sourceReference != null) Text(question.sourceReference!, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
          ]),
          const SizedBox(height: 10),
          Text(question.text, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, height: 1.6)),
          const SizedBox(height: 8),
          for (final option in question.options)
            Container(
              margin: const EdgeInsets.only(top: 6),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(10),
                color: option.id == correctId
                    ? Colors.green.shade50
                    : (showStudentChoice && option.id == chosen ? Colors.red.shade50 : Colors.grey.shade50),
                border: Border.all(
                  color: option.id == correctId ? Colors.green : (showStudentChoice && option.id == chosen ? Colors.red : Colors.grey.shade200),
                ),
              ),
              child: Row(children: [
                Icon(
                  option.id == correctId ? Icons.check_circle : (showStudentChoice && option.id == chosen ? Icons.cancel : Icons.circle_outlined),
                  size: 18,
                  color: option.id == correctId ? Colors.green : (showStudentChoice && option.id == chosen ? Colors.red : Colors.grey),
                ),
                const SizedBox(width: 8),
                Expanded(child: Text(option.text)),
                if (showStudentChoice && option.id == chosen) Text('إجابتك', style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
              ]),
            ),
          if (showStudentChoice && chosen == null)
            Padding(padding: const EdgeInsets.only(top: 6), child: Text('لم تتم الإجابة', style: TextStyle(color: Colors.orange.shade800, fontSize: 13))),
          if ((question.explanation ?? '').isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(10)),
              child: Text.rich(TextSpan(children: [
                const TextSpan(text: 'الشرح: ', style: TextStyle(fontWeight: FontWeight.w700)),
                TextSpan(text: question.explanation),
              ])),
            ),
          ],
          if (question.anchors.isNotEmpty)
            Align(
              alignment: AlignmentDirectional.centerEnd,
              child: TextButton.icon(
                icon: const Icon(Icons.highlight, size: 18),
                label: const Text('اعرض في الملزمة'),
                onPressed: () {
                  final anchor = question.anchors.first;
                  Navigator.push(context, MaterialPageRoute(builder: (_) => ReaderScreen(bookletId: anchor.bookletId, highlight: anchor)));
                },
              ),
            ),
        ]),
      ),
    );
  }
}
