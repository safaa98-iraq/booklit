// Plain data classes mirroring the Laravel API resources.

int _int(dynamic v) => v is int ? v : int.tryParse('$v') ?? 0;
double _double(dynamic v) => v is num ? v.toDouble() : double.tryParse('$v') ?? 0;
List<Map<String, dynamic>> _list(dynamic v) => (v as List? ?? []).cast<Map<String, dynamic>>();

class AppUser {
  AppUser({required this.id, required this.name, required this.phone, required this.watermark});
  final int id;
  final String name;
  final String phone;
  final String watermark;

  factory AppUser.fromJson(Map<String, dynamic> j) => AppUser(
        id: _int(j['id']),
        name: j['name'] ?? '',
        phone: j['phone'] ?? '',
        watermark: j['watermark'] ?? '',
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'phone': phone, 'watermark': watermark};
}

class FourthButton {
  FourthButton({required this.enabled, required this.label, required this.icon, required this.action, this.value});
  final bool enabled;
  final String label;
  final String icon;
  final String action; // none | url | route
  final String? value;

  factory FourthButton.fromJson(Map<String, dynamic>? j) => FourthButton(
        enabled: j?['enabled'] == true,
        label: (j?['label'] as String?)?.trim().isNotEmpty == true ? j!['label'] : 'قريباً',
        icon: j?['icon'] ?? 'star',
        action: j?['action'] ?? 'none',
        value: j?['value'],
      );
}

class AppSettings {
  AppSettings({required this.telegramUrl, required this.lockedMessage, required this.lockedButton, required this.fourthButton});
  final String telegramUrl;
  final String lockedMessage;
  final String lockedButton;
  final FourthButton fourthButton;

  factory AppSettings.fromJson(Map<String, dynamic> j) => AppSettings(
        telegramUrl: j['telegram_url'] ?? '',
        lockedMessage: j['locked_subject_message'] ?? 'هذه المادة متاحة للمشتركين فقط.',
        lockedButton: j['locked_subject_button'] ?? 'تواصل عبر تيليجرام',
        fourthButton: FourthButton.fromJson(j['fourth_button'] as Map<String, dynamic>?),
      );
}

class Subject {
  Subject({required this.id, required this.stageId, required this.name, this.description, this.color, required this.isLocked, required this.isMinisterial, this.booklets = const []});
  final int id;
  final int stageId;
  final String name;
  final String? description;
  final String? color;
  final bool isLocked;
  final bool isMinisterial;
  final List<BookletSummary> booklets;

  factory Subject.fromJson(Map<String, dynamic> j) => Subject(
        id: _int(j['id']),
        stageId: _int(j['stage_id']),
        name: j['name'] ?? '',
        description: j['description'],
        color: j['color'],
        isLocked: j['is_locked'] == true,
        isMinisterial: j['is_ministerial'] == true,
        booklets: _list(j['booklets']).map(BookletSummary.fromJson).toList(),
      );
}

class Stage {
  Stage({required this.id, required this.name, required this.isMinisterial, required this.subjects});
  final int id;
  final String name;
  final bool isMinisterial;
  final List<Subject> subjects;

  factory Stage.fromJson(Map<String, dynamic> j) => Stage(
        id: _int(j['id']),
        name: j['name'] ?? '',
        isMinisterial: j['is_ministerial'] == true,
        subjects: _list(j['subjects']).map(Subject.fromJson).toList(),
      );
}

class BookletSummary {
  BookletSummary({required this.id, required this.title, this.description});
  final int id;
  final String title;
  final String? description;

  factory BookletSummary.fromJson(Map<String, dynamic> j) =>
      BookletSummary(id: _int(j['id']), title: j['title'] ?? '', description: j['description']);
}

class ContentBlock {
  ContentBlock({required this.id, required this.type, required this.data, required this.plainText});
  final String id; // stable UUID
  final String type;
  final Map<String, dynamic> data;
  final String plainText;

  factory ContentBlock.fromJson(Map<String, dynamic> j) => ContentBlock(
        id: j['id'] ?? '',
        type: j['type'] ?? 'paragraph',
        data: (j['data'] as Map?)?.cast<String, dynamic>() ?? {},
        plainText: j['plain_text'] ?? '',
      );
}

class Topic {
  Topic({required this.id, required this.title, required this.blocks});
  final int id;
  final String title;
  final List<ContentBlock> blocks;

  factory Topic.fromJson(Map<String, dynamic> j) => Topic(
        id: _int(j['id']),
        title: j['title'] ?? '',
        blocks: _list(j['blocks']).map(ContentBlock.fromJson).toList(),
      );
}

class ModeAction {
  ModeAction({required this.enabled, required this.count});
  final bool enabled;
  final int count;
}

class Booklet {
  Booklet({
    required this.id,
    required this.title,
    required this.subjectId,
    required this.subjectName,
    required this.topics,
    required this.ministerial,
    required this.teacher,
    required this.quizEnabled,
    required this.quizSize,
    required this.quizByDifficulty,
    required this.fourthButton,
  });

  final int id;
  final String title;
  final int subjectId;
  final String subjectName;
  final List<Topic> topics;
  final ModeAction ministerial;
  final ModeAction teacher;
  final bool quizEnabled;
  final int quizSize;
  final Map<String, int> quizByDifficulty;
  final FourthButton fourthButton;

  factory Booklet.fromJson(Map<String, dynamic> j) {
    final actions = (j['actions'] as Map?)?.cast<String, dynamic>() ?? {};
    final quiz = (actions['quiz'] as Map?)?.cast<String, dynamic>() ?? {};
    ModeAction mode(String key) {
      final m = (actions[key] as Map?)?.cast<String, dynamic>() ?? {};
      return ModeAction(enabled: m['enabled'] == true, count: _int(m['questions_count']));
    }

    return Booklet(
      id: _int(j['id']),
      title: j['title'] ?? '',
      subjectId: _int(j['subject']?['id']),
      subjectName: j['subject']?['name'] ?? '',
      topics: _list(j['topics']).map(Topic.fromJson).toList(),
      ministerial: mode('mcq_ministerial'),
      teacher: mode('mcq_teacher'),
      quizEnabled: quiz['enabled'] == true,
      quizSize: _int(quiz['question_count']),
      quizByDifficulty: ((quiz['available_by_difficulty'] as Map?) ?? {}).map((k, v) => MapEntry('$k', _int(v))),
      fourthButton: FourthButton.fromJson((actions['fourth_button'] as Map?)?.cast<String, dynamic>()),
    );
  }
}

/// Where a question's source passage lives in the booklet.
class PassageAnchor {
  PassageAnchor({required this.blockId, required this.topicId, required this.bookletId, required this.start, required this.end, this.text});
  final String blockId;
  final int topicId;
  final int bookletId;
  final int start;
  final int end;
  final String? text;

  factory PassageAnchor.fromJson(Map<String, dynamic> j) => PassageAnchor(
        blockId: j['block_id'] ?? '',
        topicId: _int(j['topic_id']),
        bookletId: _int(j['booklet_id']),
        start: _int(j['start']),
        end: _int(j['end']),
        text: j['text'],
      );
}

class AnswerOption {
  AnswerOption({required this.id, required this.text, this.isCorrect});
  final int id;
  final String text;
  final bool? isCorrect;

  factory AnswerOption.fromJson(Map<String, dynamic> j) =>
      AnswerOption(id: _int(j['id']), text: j['text'] ?? '', isCorrect: j['is_correct'] as bool?);
}

/// A question inside an attempt, or in review ("أظهر الحل") mode.
class QuestionItem {
  QuestionItem({
    required this.id,
    required this.text,
    required this.options,
    this.difficulty,
    this.source,
    this.selectedOptionId,
    this.isCorrect,
    this.correctOptionId,
    this.explanation,
    this.sourceReference,
    this.anchors = const [],
  });

  final int id;
  final String text;
  final List<AnswerOption> options;
  final String? difficulty;
  final String? source;
  int? selectedOptionId;
  final bool? isCorrect;
  final int? correctOptionId;
  final String? explanation;
  final String? sourceReference;
  final List<PassageAnchor> anchors;

  factory QuestionItem.fromJson(Map<String, dynamic> j) => QuestionItem(
        id: _int(j['question_id'] ?? j['id']),
        text: j['text'] ?? '',
        options: _list(j['options']).map(AnswerOption.fromJson).toList(),
        difficulty: j['difficulty'],
        source: j['source'],
        selectedOptionId: j['selected_option_id'] == null ? null : _int(j['selected_option_id']),
        isCorrect: j['is_correct'] as bool?,
        correctOptionId: j['correct_option_id'] == null ? null : _int(j['correct_option_id']),
        explanation: j['explanation'],
        sourceReference: j['source_reference'],
        anchors: _list(j['anchors']).map(PassageAnchor.fromJson).toList(),
      );
}

class Attempt {
  Attempt({
    required this.id,
    required this.mode,
    required this.status,
    required this.total,
    required this.questions,
    this.difficulty,
    this.bookletId,
    this.correctCount,
    this.score,
  });

  final int id;
  final String mode;
  final String status;
  final int total;
  final List<QuestionItem> questions;
  final String? difficulty;
  final int? bookletId;
  final int? correctCount;
  final double? score;

  bool get isSubmitted => status == 'submitted';

  factory Attempt.fromJson(Map<String, dynamic> j) => Attempt(
        id: _int(j['id']),
        mode: j['mode'] ?? '',
        status: j['status'] ?? '',
        total: _int(j['total_questions']),
        difficulty: j['difficulty'],
        bookletId: j['booklet_id'] == null ? null : _int(j['booklet_id']),
        correctCount: j['correct_count'] == null ? null : _int(j['correct_count']),
        score: j['score'] == null ? null : _double(j['score']),
        questions: _list(j['questions']).map(QuestionItem.fromJson).toList(),
      );
}

const difficultyLabels = {'easy': 'سهل', 'medium': 'متوسط', 'hard': 'صعب'};
const modeLabels = {'ministerial': 'MCQ وزاري', 'teacher': 'MCQ الأستاذ', 'quiz': 'وضع الاختبار'};
