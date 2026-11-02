import 'package:booklit_student/core/models.dart';
import 'package:booklit_student/widgets/block_view.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// Collects the highlighted span texts rendered by a BlockView.
List<String> highlighted(WidgetTester tester) {
  final out = <String>[];
  for (final rich in tester.widgetList<RichText>(find.byType(RichText))) {
    rich.text.visitChildren((span) {
      if (span is TextSpan && span.style?.backgroundColor != null && span.text != null) out.add(span.text!);
      return true;
    });
  }
  return out;
}

Widget host(Widget child) => MaterialApp(home: Directionality(textDirection: TextDirection.rtl, child: Scaffold(body: child)));

void main() {
  testWidgets('highlights the anchored range inside a paragraph', (tester) async {
    final block = ContentBlock(id: 'b1', type: 'paragraph', data: {'text': 'الخلية هي الوحدة الأساسية'}, plainText: 'الخلية هي الوحدة الأساسية');
    await tester.pumpWidget(host(BlockView(block: block, highlight: (0, 6))));
    expect(highlighted(tester), ['الخلية']);
  });

  testWidgets('maps plain-text offsets onto the right list item', (tester) async {
    const items = ['Enamel: أصلب نسيج', 'Dentin: العاج'];
    final block = ContentBlock(id: 'b2', type: 'list', data: {'style': 'bullet', 'items': items}, plainText: items.join('\n'));
    final start = items[0].runes.length + 1; // second item starts after "\n"
    await tester.pumpWidget(host(BlockView(block: block, highlight: (start, start + items[1].runes.length))));
    expect(highlighted(tester), ['Dentin: العاج']);
  });

  testWidgets('no highlight when no anchor', (tester) async {
    final block = ContentBlock(id: 'b3', type: 'paragraph', data: {'text': 'نص'}, plainText: 'نص');
    await tester.pumpWidget(host(BlockView(block: block)));
    expect(highlighted(tester), isEmpty);
  });
}
