<?php

namespace Tests\Feature;

use App\Enums\AttemptMode;
use App\Enums\Difficulty;
use App\Http\Middleware\EnsureStudentWebDevice;
use App\Models\Attempt;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\TopicProgress;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\DeviceBindingService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWebsiteTest extends TestCase
{
    use RefreshDatabase;

    private const COOKIE = 'browser-one-0123456789abcdefghij';

    private function signIn(User $student, string $cookie = self::COOKIE): User
    {
        $this->withCookie(EnsureStudentWebDevice::COOKIE, $cookie)
            ->post('/login', ['phone' => $student->phone, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($student, 'student');

        return $student;
    }

    /** A free booklet with two topics; the first topic has blocks and questions. */
    private function bookletWithTopics(): array
    {
        $booklet = Booklet::factory()->create(['title' => 'ملزمة التشريح', 'quiz_question_count' => 2]);
        $first = Topic::factory()->for($booklet)->create(['title' => 'أجزاء السن', 'sort_order' => 1]);
        $second = Topic::factory()->for($booklet)->create(['title' => 'تسمية الأسنان', 'sort_order' => 2]);

        return [$booklet, $first, $second];
    }

    /* ---------------------------------------------------------------- Auth & device */

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/library')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('BOOKLIT');
        $this->get('/app/old/link')->assertRedirect('/');
    }

    public function test_register_binds_this_browser(): void
    {
        $this->withCookie(EnsureStudentWebDevice::COOKIE, self::COOKIE)->post('/register', [
            'name' => 'سارة علي', 'phone' => '0770 555 1234',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect('/');

        $student = User::where('phone', '07705551234')->firstOrFail();
        $this->assertSame('web-'.self::COOKIE, $student->activeDevice->device_id);
    }

    public function test_login_from_another_browser_is_rejected_and_reset_signs_out(): void
    {
        $student = User::factory()->create();
        app(DeviceBindingService::class)->bind($student, ['device_id' => 'web-other-browser']);

        $this->withCookie(EnsureStudentWebDevice::COOKIE, self::COOKIE)->from('/login')
            ->post('/login', ['phone' => $student->phone, 'password' => 'password'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest('student');

        app(DeviceBindingService::class)->reset($student);
        $this->signIn($student);
        app(DeviceBindingService::class)->reset($student);
        $this->get('/')->assertRedirect('/login');
    }

    /* ---------------------------------------------------------------- Home & library */

    public function test_home_shows_continue_reading_from_the_saved_position(): void
    {
        [$booklet, $first, $second] = $this->bookletWithTopics();
        $student = $this->signIn(User::factory()->create(['name' => 'يوسف سعد']));

        $this->get('/')->assertOk()->assertSee('ابدأ من هنا')->assertSee('يوسف سعد');

        TopicProgress::create(['user_id' => $student->id, 'topic_id' => $second->id, 'booklet_id' => $booklet->id, 'scroll_percent' => 40, 'last_read_at' => now()]);

        $this->get('/')->assertOk()
            ->assertSee('تابع القراءة')
            ->assertSee('تسمية الأسنان')
            ->assertSee(route('student.topics.show', $second), false);

        // Opening the booklet resumes at the last topic read.
        $this->get("/booklets/{$booklet->id}")->assertRedirect(route('student.topics.show', $second));
    }

    public function test_library_shows_paid_subjects_with_a_lock_and_server_blocks_them(): void
    {
        $free = Subject::factory()->create(['name' => 'تشريح الأسنان']);
        $paid = Subject::factory()->for($free->stage)->ministerial()->create(['name' => 'جراحة الفم']);
        $paidBooklet = Booklet::factory()->for($paid)->create();
        $paidTopic = Topic::factory()->for($paidBooklet)->create();
        $student = $this->signIn(User::factory()->create());

        $this->get('/library')->assertOk()
            ->assertSee('تشريح الأسنان')->assertSee('جراحة الفم')->assertSee('للمشتركين')
            ->assertSee(route('student.library.subject', $free), false)
            ->assertDontSee(route('student.library.subject', $paid), false);

        // Paid content is refused on the server, not just hidden.
        foreach (["/library/subjects/{$paid->id}", "/booklets/{$paidBooklet->id}", "/topics/{$paidTopic->id}"] as $url) {
            $this->get($url)->assertRedirect(route('student.library'))->assertSessionHas('locked');
        }
        $this->withCredentials()->postJson("/topics/{$paidTopic->id}/progress", ['scroll' => 10, 'seconds' => 5])->assertForbidden();

        app(SubscriptionService::class)->activate($student, null, [$paid->id], now()->addMonth());
        $this->get("/topics/{$paidTopic->id}")->assertOk();
    }

    public function test_library_subject_lists_booklets_and_selected_topics(): void
    {
        [$booklet, $first, $second] = $this->bookletWithTopics();
        $other = Booklet::factory()->for($booklet->subject)->create(['title' => 'ملزمة ثانية']);
        Topic::factory()->for($other)->create(['title' => 'موضوع الملزمة الثانية']);
        $this->signIn(User::factory()->create());

        $this->get("/library/subjects/{$booklet->subject_id}")->assertOk()
            ->assertSee('ملزمة التشريح')->assertSee('ملزمة ثانية')->assertSee('أجزاء السن');

        $this->get("/library/subjects/{$booklet->subject_id}?booklet={$other->id}")->assertOk()
            ->assertSee('موضوع الملزمة الثانية')->assertDontSee('أجزاء السن');
    }

    /* ---------------------------------------------------------------- Reader */

    public function test_reader_shows_one_topic_with_navigation_and_highlights(): void
    {
        [$booklet, $first, $second] = $this->bookletWithTopics();
        $list = ContentBlock::factory()->for($first)->create(['type' => 'list', 'data' => ['style' => 'bullet', 'items' => ['Enamel: المينا', 'Dentin: العاج']]]);
        ContentBlock::factory()->for($second)->create(['data' => ['text' => 'نص الموضوع الثاني فقط']]);
        $student = $this->signIn(User::factory()->create());
        $student->highlights()->create(['content_block_id' => $list->id, 'start_offset' => 0, 'end_offset' => 6]);

        $start = mb_strlen('Enamel: المينا') + 1;
        $this->get("/topics/{$first->id}?hl={$list->uuid}:{$start}:".($start + 13))->assertOk()
            ->assertSee('أجزاء السن')
            ->assertDontSee('نص الموضوع الثاني فقط')               // one topic per page
            ->assertSee('الموضوع 1 من 2')
            ->assertSee(route('student.topics.show', $second), false) // next topic
            ->assertSee('<mark class="anchor">Dentin: العاج</mark>', false)
            ->assertSee('<mark class="user" data-hl="', false)
            ->assertSee('MCQ وزاري')->assertSee('وضع الاختبار')->assertSee('أدوات القراءة');
    }

    public function test_reader_renders_tables_and_anchors_inside_cells(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        $table = ContentBlock::factory()->for($first)->create(['type' => 'table', 'data' => ['header' => true, 'rows' => [['المصطلح', 'التعريف'], ['Pulp', 'اللب']]]]);
        $this->signIn(User::factory()->create());

        $this->assertSame("المصطلح | التعريف\nPulp | اللب", $table->plain_text);
        $start = mb_strpos($table->plain_text, 'اللب');

        $this->get("/topics/{$first->id}?hl={$table->uuid}:{$start}:".($start + 4))->assertOk()
            ->assertSee('<th data-base="0">المصطلح</th>', false)
            ->assertSee('<mark class="anchor">اللب</mark>', false);
    }

    public function test_reading_progress_is_saved_and_completes_the_topic(): void
    {
        [$booklet, $first, $second] = $this->bookletWithTopics();
        $student = $this->signIn(User::factory()->create());

        $this->withCredentials()->postJson("/topics/{$first->id}/progress", ['scroll' => 40, 'seconds' => 15])->assertOk()->assertJson(['completed' => false]);
        $this->withCredentials()->postJson("/topics/{$first->id}/progress", ['scroll' => 95, 'seconds' => 500])->assertOk()->assertJson(['completed' => true]);

        $row = TopicProgress::where('user_id', $student->id)->where('topic_id', $first->id)->sole();
        $this->assertSame(95, $row->scroll_percent);
        $this->assertSame(75, $row->reading_seconds); // 15 + capped 60
        $this->assertNotNull($row->completed_at);

        $this->get('/progress')->assertOk()->assertSee('50%')->assertSee('1 من 2 موضوع مكتمل');
        $this->get("/progress/subjects/{$booklet->subject_id}")->assertOk()->assertSee('مكتمل')->assertSee('لم يبدأ');
    }

    public function test_booklet_search_and_global_search(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        $block = ContentBlock::factory()->for($first)->create(['data' => ['text' => 'الميتوكوندريا مصنع الطاقة']]);
        $this->signIn(User::factory()->create());

        $this->withCredentials()->getJson("/booklets/{$booklet->id}/search?q=مصنع")->assertOk()
            ->assertJsonPath('results.0.topic', 'أجزاء السن')
            ->assertJsonPath('results.0.url', fn ($url) => str_contains($url, "hl={$block->uuid}"));

        $this->get('/search?q=مصنع')->assertOk()->assertSee('أجزاء السن');
        $this->get('/search?q=التشريح')->assertOk()->assertSee('ملزمة التشريح');
    }

    public function test_student_highlights_can_be_created_and_deleted(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        $block = ContentBlock::factory()->for($first)->create(['data' => ['text' => 'الخلية وحدة الحياة']]);
        $student = $this->signIn(User::factory()->create());

        $id = $this->withCredentials()->postJson('/highlights', ['block' => $block->uuid, 'start' => 0, 'end' => 6])->assertCreated()->json('id');
        $this->get("/topics/{$first->id}")->assertSee('<mark class="user" data-hl="'.$id.'">الخلية</mark>', false);

        $other = $this->signInAs(User::factory()->create(), 'browser-two-0123456789abcdefghij');
        $this->withCredentials()->deleteJson("/highlights/{$id}")->assertForbidden();

        $this->assertDatabaseHas('user_highlights', ['id' => $id, 'user_id' => $student->id]);
        $this->assertNotNull($other);
    }

    private function signInAs(User $student, string $cookie): User
    {
        $this->post('/logout');
        $this->app['auth']->forgetGuards();

        return $this->signIn($student, $cookie);
    }

    /* ---------------------------------------------------------------- Quizzes */

    public function test_topic_quiz_uses_only_the_current_topics_questions(): void
    {
        [$booklet, $first, $second] = $this->bookletWithTopics();
        $block = ContentBlock::factory()->for($first)->create(['data' => ['text' => 'الميتوكوندريا مصنع الطاقة']]);
        $mine = Question::factory()->count(3)->forTopic($first)->ministerial()->withOptions(4)->create();
        Question::factory()->count(5)->forTopic($second)->ministerial()->withOptions(4)->create();
        $mine[0]->anchors()->create(['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 13]);
        $student = $this->signIn(User::factory()->create());

        $this->post("/topics/{$first->id}/attempts", ['mode' => 'ministerial'])->assertRedirect();
        $attempt = Attempt::where('user_id', $student->id)->sole();
        $this->assertSame($first->id, $attempt->topic_id);
        $this->assertSame(3, $attempt->total_questions);

        $this->post("/attempts/{$attempt->id}/submit", ['answers' => [
            $mine[0]->id => $mine[0]->correctOption->id,
            $mine[1]->id => $mine[1]->correctOption->id,
            $mine[2]->id => $mine[2]->options()->where('is_correct', false)->value('id'),
        ]])->assertRedirect("/attempts/{$attempt->id}");

        $this->get("/attempts/{$attempt->id}")->assertOk()
            ->assertSee('67%')
            ->assertSee('اعرض في الملزمة')
            ->assertSee(route('student.topics.show', [$first, 'hl' => "{$block->uuid}:0:13"]), false);

        $this->get("/topics/{$first->id}/review/ministerial")->assertOk()->assertSee('الشرح');
    }

    public function test_quiz_mode_requires_difficulty(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        Question::factory()->count(4)->forTopic($first)->teacher()->difficulty(Difficulty::Hard)->withOptions(3)->create();
        $student = $this->signIn(User::factory()->create());

        $this->post("/topics/{$first->id}/attempts", ['mode' => 'quiz'])->assertSessionHasErrors('difficulty');
        $this->post("/topics/{$first->id}/attempts", ['mode' => 'quiz', 'difficulty' => 'hard'])->assertRedirect();
        $this->assertSame(2, Attempt::where('user_id', $student->id)->sole()->total_questions); // editor's quiz size
    }

    public function test_students_cannot_open_other_students_attempts(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        Question::factory()->forTopic($first)->teacher()->withOptions()->create();
        $attempt = app(AttemptService::class)->start(User::factory()->create(), AttemptMode::Teacher, $booklet, null, null, $first);

        $this->signIn(User::factory()->create());
        $this->get("/attempts/{$attempt->id}")->assertForbidden();
        $this->post("/attempts/{$attempt->id}/submit")->assertForbidden();
    }

    /* ---------------------------------------------------------------- Favorites & profile */

    public function test_favorites_toggle_for_booklets_and_topics(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        $this->signIn(User::factory()->create());

        $this->get('/favorites')->assertOk()->assertSee('لا توجد عناصر محفوظة');

        $this->withCredentials()->postJson('/favorites/toggle', ['type' => 'booklet', 'id' => $booklet->id])->assertJson(['favorite' => true]);
        $this->withCredentials()->postJson('/favorites/toggle', ['type' => 'topic', 'id' => $first->id])->assertJson(['favorite' => true]);

        $this->get('/favorites')->assertSee('ملزمة التشريح');
        $this->get('/favorites?tab=topics')->assertSee('أجزاء السن');

        $this->withCredentials()->postJson('/favorites/toggle', ['type' => 'booklet', 'id' => $booklet->id])->assertJson(['favorite' => false]);
        $this->get('/favorites')->assertSee('لا توجد عناصر محفوظة');
    }

    public function test_profile_stats_and_settings_pages(): void
    {
        [$booklet, $first] = $this->bookletWithTopics();
        $student = $this->signIn(User::factory()->create(['name' => 'نور الهدى']));
        TopicProgress::create(['user_id' => $student->id, 'topic_id' => $first->id, 'booklet_id' => $booklet->id, 'reading_seconds' => 3900, 'last_read_at' => now()]);

        $this->get('/profile')->assertOk()
            ->assertSee('نور الهدى')->assertSee('1 س 5 د')->assertSee('نسبة الدقة')
            ->assertSee('تغيير كلمة المرور')->assertSee('عن BOOKLIT')->assertSee('تسجيل الخروج');

        foreach (['/profile/edit', '/profile/password', '/profile/notifications', '/profile/appearance', '/profile/language', '/profile/recent', '/profile/attempts', '/profile/help', '/profile/about', '/notifications'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->put('/profile/edit', ['name' => 'نور الهدى صادق', 'email' => 'noor@example.com'])->assertRedirect('/profile');
        $this->assertSame('noor@example.com', $student->fresh()->email);

        $this->put('/profile/password', ['current_password' => 'wrong', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertSessionHasErrors('current_password');
        $this->put('/profile/password', ['current_password' => 'password', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertRedirect('/profile');

        $this->put('/profile/appearance', ['font_size' => 22])->assertRedirect('/profile');
        $this->assertSame(22, $student->fresh()->preference('font_size'));
        $this->get("/topics/{$first->id}")->assertSee('--fs: 22px', false);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest('student');
    }
}
