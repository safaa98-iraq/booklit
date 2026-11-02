import 'api_client.dart';
import 'models.dart';

/// All API calls used by the screens.
class Repository {
  Repository._();
  static final Repository instance = Repository._();
  final _api = ApiClient.instance;

  Future<AppSettings> settings() async => AppSettings.fromJson((await _api.get('/settings'))['data'] ?? {});

  Future<List<Stage>> stages() async =>
      ((await _api.get('/stages'))['data'] as List).cast<Map<String, dynamic>>().map(Stage.fromJson).toList();

  Future<Subject> subject(int id) async => Subject.fromJson((await _api.get('/subjects/$id'))['data']);

  Future<Booklet> booklet(int id) async => Booklet.fromJson((await _api.get('/booklets/$id'))['data']);

  Future<List<QuestionItem>> review(int bookletId, String source) async =>
      ((await _api.get('/booklets/$bookletId/questions/review', query: {'source': source}))['data'] as List)
          .cast<Map<String, dynamic>>()
          .map(QuestionItem.fromJson)
          .toList();

  Future<Attempt> startAttempt({required int bookletId, required String mode, String? difficulty}) async =>
      Attempt.fromJson((await _api.post('/attempts', {
        'booklet_id': bookletId,
        'mode': mode,
        'difficulty': ?difficulty,
      }))['data']);

  Future<void> saveAnswer(int attemptId, int questionId, int? optionId) => _api.put('/attempts/$attemptId/answers', {
        'answers': [
          {'question_id': questionId, 'option_id': optionId},
        ],
      });

  Future<Attempt> submit(int attemptId, Map<int, int?> answers) async =>
      Attempt.fromJson((await _api.post('/attempts/$attemptId/submit', {
        'answers': answers.entries.map((e) => {'question_id': e.key, 'option_id': e.value}).toList(),
      }))['data']);

  Future<List<Attempt>> history() async =>
      ((await _api.get('/attempts'))['data'] as List).cast<Map<String, dynamic>>().map(Attempt.fromJson).toList();
}
