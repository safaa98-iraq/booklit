import 'package:flutter/material.dart';
import 'package:flutter_math_fork/flutter_math.dart';

import '../core/models.dart';

/// Renders one content block. If [highlight] is set, the code-point range
/// [start, end) of the block's plain text is highlighted (passage anchor).
class BlockView extends StatelessWidget {
  const BlockView({super.key, required this.block, this.highlight});

  final ContentBlock block;
  final (int start, int end)? highlight;

  static const _highlightColor = Color(0xFFFFE58F);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final data = block.data;
    final body = theme.textTheme.bodyLarge!.copyWith(height: 1.9, fontSize: 17);

    switch (block.type) {
      case 'heading':
        final level = (data['level'] as num?)?.toInt() ?? 2;
        final style = (level == 2 ? theme.textTheme.titleLarge : theme.textTheme.titleMedium)!
            .copyWith(fontWeight: FontWeight.w700, color: level == 2 ? theme.colorScheme.primary : null, height: 1.6);
        return Padding(
          padding: EdgeInsets.only(top: level == 2 ? 12 : 6, bottom: 4),
          child: Text.rich(_spans(block.plainText, 0, style)),
        );

      case 'list':
        final items = (data['items'] as List? ?? []).map((e) => '$e').toList();
        final ordered = data['style'] == 'ordered';
        var offset = 0;
        final rows = <Widget>[];
        for (var i = 0; i < items.length; i++) {
          final start = offset;
          rows.add(Padding(
            padding: const EdgeInsets.symmetric(vertical: 3),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(
                  width: 26,
                  child: Text(ordered ? '${i + 1}.' : '•', style: body.copyWith(color: theme.colorScheme.primary, fontWeight: FontWeight.bold)),
                ),
                Expanded(child: Text.rich(_spans(items[i], start, body))),
              ],
            ),
          ));
          offset += items[i].runes.length + 1; // "\n" joins items in plain_text
        }
        return Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: Column(children: rows));

      case 'quote':
        final variant = data['variant'] ?? 'note';
        final color = switch (variant) { 'warning' => Colors.orange, 'tip' => Colors.teal, 'definition' => Colors.indigo, _ => Colors.blueGrey };
        return Container(
          margin: const EdgeInsets.symmetric(vertical: 8),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(12),
            border: BorderDirectional(start: BorderSide(color: color, width: 4)),
          ),
          child: Text.rich(_spans(block.plainText, 0, body.copyWith(fontSize: 15.5))),
        );

      case 'math':
        final highlighted = highlight != null;
        return Container(
          margin: const EdgeInsets.symmetric(vertical: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: highlighted ? _highlightColor : Colors.white, borderRadius: BorderRadius.circular(12)),
          alignment: Alignment.center,
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Directionality(
              textDirection: TextDirection.ltr,
              child: Math.tex(data['latex'] ?? '', textStyle: const TextStyle(fontSize: 20), onErrorFallback: (e) => Text(data['latex'] ?? '')),
            ),
          ),
        );

      case 'image':
        final url = data['url'] as String?;
        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Column(children: [
            if (url != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(url, errorBuilder: (_, _, _) => const Icon(Icons.broken_image_outlined, size: 48)),
              ),
            if ((data['caption'] ?? '').toString().isNotEmpty)
              Padding(padding: const EdgeInsets.only(top: 6), child: Text(data['caption'], style: theme.textTheme.bodySmall)),
          ]),
        );

      default: // paragraph
        return Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Text.rich(_spans(block.plainText, 0, body)));
    }
  }

  /// Splits [text] (which starts at code point [base] of the block) around the highlight range.
  TextSpan _spans(String text, int base, TextStyle style) {
    final h = highlight;
    final runes = text.runes.toList();
    if (h == null) return TextSpan(text: text, style: style);

    final from = (h.$1 - base).clamp(0, runes.length);
    final to = (h.$2 - base).clamp(0, runes.length);
    if (from >= to) return TextSpan(text: text, style: style);

    String part(int a, int b) => String.fromCharCodes(runes.sublist(a, b));
    return TextSpan(style: style, children: [
      TextSpan(text: part(0, from)),
      TextSpan(text: part(from, to), style: const TextStyle(backgroundColor: _highlightColor, fontWeight: FontWeight.w700)),
      TextSpan(text: part(to, runes.length)),
    ]);
  }
}
