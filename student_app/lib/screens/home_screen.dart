import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../core/repository.dart';
import '../core/session.dart';
import '../core/theme.dart';
import '../widgets/common.dart';
import 'account_sheet.dart';
import 'reader_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Stage>> _future = Repository.instance.stages();
  int? _stageId;

  @override
  void initState() {
    super.initState();
    SharedPreferences.getInstance().then((p) {
      if (mounted) setState(() => _stageId = p.getInt('stage_id'));
    });
  }

  void _selectStage(int id) {
    setState(() => _stageId = id);
    SharedPreferences.getInstance().then((p) => p.setInt('stage_id', id));
  }

  Future<void> _refresh() async {
    setState(() => _future = Repository.instance.stages());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final user = Session.instance.user;
    return Scaffold(
      appBar: AppBar(
        title: const Text('بوكلت', style: TextStyle(fontWeight: FontWeight.w800)),
        actions: [
          IconButton(
            tooltip: 'حسابي',
            icon: CircleAvatar(radius: 16, child: Text(user?.name.characters.first ?? '؟')),
            onPressed: () => showAccountSheet(context),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: AsyncView<List<Stage>>(
        future: _future,
        onRetry: _refresh,
        builder: (context, stages) {
          if (stages.isEmpty) return const Center(child: Text('لا توجد مراحل متاحة حالياً.'));
          final stage = stages.firstWhere((s) => s.id == _stageId, orElse: () => stages.first);

          return RefreshIndicator(
            onRefresh: _refresh,
            child: CustomScrollView(slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
                  child: Text('أهلاً ${user?.name.split(' ').first ?? ''} 👋', style: Theme.of(context).textTheme.titleLarge!.copyWith(fontWeight: FontWeight.w700)),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final s in stages)
                        ChoiceChip(
                          label: Text(s.name),
                          avatar: s.isMinisterial ? const Icon(Icons.workspace_premium, size: 18) : null,
                          selected: s.id == stage.id,
                          onSelected: (_) => _selectStage(s.id),
                        ),
                    ],
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverGrid.builder(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 12,
                    crossAxisSpacing: 12,
                    childAspectRatio: 1.35,
                  ),
                  itemCount: stage.subjects.length,
                  itemBuilder: (context, i) => SubjectCard(subject: stage.subjects[i]),
                ),
              ),
            ]),
          );
        },
      ),
    );
  }
}

class SubjectCard extends StatelessWidget {
  const SubjectCard({super.key, required this.subject});
  final Subject subject;

  @override
  Widget build(BuildContext context) {
    final color = AppTheme.fromHex(subject.color);
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => openSubject(context, subject),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade200),
            gradient: LinearGradient(
              begin: AlignmentDirectional.topStart,
              end: AlignmentDirectional.bottomEnd,
              colors: [color.withValues(alpha: subject.isLocked ? 0.06 : 0.14), Colors.white],
            ),
          ),
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(10)),
                child: Icon(Icons.menu_book_rounded, color: color, size: 22),
              ),
              const Spacer(),
              if (subject.isLocked)
                Icon(Icons.lock_rounded, color: Colors.grey.shade600, size: 22)
              else if (subject.isMinisterial)
                Icon(Icons.verified_rounded, color: Colors.green.shade600, size: 20),
            ]),
            const Spacer(),
            Text(
              subject.name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: subject.isLocked ? Colors.grey.shade600 : Colors.black87),
            ),
            if (subject.isLocked)
              Text('للمشتركين فقط', style: TextStyle(fontSize: 12, color: Colors.grey.shade600))
            else if (subject.isMinisterial)
              Text('مفعّلة', style: TextStyle(fontSize: 12, color: Colors.green.shade700)),
          ]),
        ),
      ),
    );
  }
}

/// Subject → its booklet. Opens the reader directly when there is a single booklet.
Future<void> openSubject(BuildContext context, Subject subject) async {
  if (subject.isLocked) {
    await showLockedDialog(context);
    return;
  }

  showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
  try {
    final full = await Repository.instance.subject(subject.id);
    if (!context.mounted) return;
    Navigator.pop(context);

    if (full.booklets.isEmpty) {
      showError(context, ApiException(0, 'لا توجد ملازم منشورة لهذه المادة بعد.'));
    } else if (full.booklets.length == 1) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => ReaderScreen(bookletId: full.booklets.first.id)));
    } else {
      Navigator.push(context, MaterialPageRoute(builder: (_) => BookletListScreen(subject: full)));
    }
  } on ApiException catch (e) {
    if (!context.mounted) return;
    Navigator.pop(context);
    e.isLocked ? showLockedDialog(context, message: e.message) : showError(context, e);
  }
}

class BookletListScreen extends StatelessWidget {
  const BookletListScreen({super.key, required this.subject});
  final Subject subject;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(subject.name)),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: subject.booklets.length,
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, i) {
          final b = subject.booklets[i];
          return Card(
            child: ListTile(
              leading: const Icon(Icons.menu_book_rounded),
              title: Text(b.title, style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: b.description == null ? null : Text(b.description!),
              trailing: const Icon(Icons.chevron_left),
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ReaderScreen(bookletId: b.id))),
            ),
          );
        },
      ),
    );
  }
}
