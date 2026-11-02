import 'package:flutter/material.dart';

import '../core/models.dart';
import '../core/repository.dart';
import '../widgets/common.dart';
import '../widgets/solution_card.dart';

/// "أظهر الحل": browse the MCQ questions with their answers, without solving.
class ReviewScreen extends StatefulWidget {
  const ReviewScreen({super.key, required this.booklet, required this.source});
  final Booklet booklet;
  final String source;

  @override
  State<ReviewScreen> createState() => _ReviewScreenState();
}

class _ReviewScreenState extends State<ReviewScreen> {
  late final Future<List<QuestionItem>> _future = Repository.instance.review(widget.booklet.id, widget.source);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('${modeLabels[widget.source]} — الحلول')),
      body: AsyncView<List<QuestionItem>>(
        future: _future,
        builder: (context, questions) => ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: questions.length,
          itemBuilder: (context, i) => SolutionCard(number: i + 1, question: questions[i], showStudentChoice: false),
        ),
      ),
    );
  }
}
