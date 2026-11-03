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

    /** Sign a student in through the login form from "browser one". */
    private function signIn(User $student, string $cookie = self::COOKIE): void
    {
        $this->withCookie(EnsureStudentWebDevice::COOKIE, $cookie)
            ->post('/login', ['phone' => $student->phone, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($student, 'student');
    }

    public function test_guests_see_the_landing_page_and_protected_pages_need_login(): void
    {
        Booklet::factory()->create();
        $this->get('/')->assertOk()->assertSee('ادرس طب الأسنان')->assertSee('إنشاء حساب');
        $this->get('/account')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('تسجيل الدخول');
        $this->get('/app/old/link')->assertRedirect('/');
    }

    public function test_register_creates_a_student_and_binds_this_browser(): void
    {
        $this->withCookie(EnsureStudentWebDevice::COOKIE, self::COOKIE)
            ->post('/register', [
                'name' => 'سارة علي',
                'phone' => '0770 555 1234',
                'password' => 'secret-pass',
                'password_confirmation' => 'secret-pass',
            ])->assertRedirect('/');

        $student = User::where('phone', '07705551234')->firstOrFail();
        $this->assertTrue($student->isStudent());
        $this->assertSame('web-'.self::COOKIE, $student->activeDevice->device_id);
        $this->assertSame('web', $student->activeDevice->platform);
    }

    public function test_login_from_another_browser_is_rejected(): void
    {
        $student = User::factory()->create();
        app(DeviceBindingService::class)->bind($student, ['device_id' => 'web-some-other-browser']);

        $this->withCookie(EnsureStudentWebDevice::COOKIE, self::COOKIE)
            ->from('/login')
            ->post('/login', ['phone' => $student->phone, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['phone' => 'هذا الحساب مرتبط بجهاز آخر. يرجى التواصل مع الإدارة لإعادة تعيين الجهاز.']);

        $this->assertGuest('student');
    }

    public function test_device_reset_by_admin_signs_the_browser_out(): void
    {
        $student = User::factory()->create();
        $this->signIn($student);

        app(DeviceBindingService::class)->reset($student);

        $this->withCookie(EnsureStudentWebDevice::COOKIE, self::COOKIE)
            ->get('/')->assertRedirect('/login');
        $this->assertGuest('student');
    }

    public function test_home_shows_subjects_with_lock_state(): void
    {
        $free = Subject::factory()->create(['name' => 'تشريح الأسنان']);
        $paid = Subject::factory()->for($free->stage)->ministerial()->create(['name' => 'جراحة الفم']);
        $this->signIn(User::factory()->create());

        $this->get('/')->assertOk()
            ->assertSee('تشريح الأسنان')
            ->assertSee('جراحة الفم')
            ->assertSee('للمشتركين فقط')
            ->assertSee(route('student.subjects.show', $free), false)
            ->assertDontSee(route('student.subjects.show', $paid), false);
    }

    public function test_locked_booklet_redirects_home_with_the_locked_dialog(): void
    {
        $booklet = Booklet::factory()->for(Subject::factory()->ministerial())->create();
        $student = User::factory()->create();
        $this->signIn($student);

        $this->get("/booklets/{$booklet->id}")->assertRedirect(route('student.home'))->assertSessionHas('locked');
        $this->get("/subjects/{$booklet->subject_id}")->assertRedirect(route('student.home'));

        app(SubscriptionService::class)->activate($student, null, [$booklet->subject_id], now()->addMonth());
        $this->get("/booklets/{$booklet->id}")->assertOk();
    }

    public function test_subject_with_one_booklet_opens_the_reader_with_highlight(): void
    {
        $booklet = Booklet::factory()->create(['title' => 'ملزمة التشريح']);
        $topic = Topic::factory()->for($booklet)->create(['title' => 'أجزاء السن']);
        $block = ContentBlock::factory()->for($topic)->create([
            'type' => 'list',
            'data' => ['style' => 'bullet', 'items' => ['Enamel: المينا', 'Dentin: العاج']],
        ]);
        $this->signIn(User::factory()->create());

        $this->get("/subjects/{$booklet->subject_id}")->assertRedirect(route('student.booklets.show', $booklet));

        $start = mb_strlen('Enamel: المينا') + 1;
        $this->get("/booklets/{$booklet->id}?hl={$block->uuid}:{$start}:".($start + 13))
            ->assertOk()
            ->assertSee('أجزاء السن')
            ->assertSee('<mark class="anchor">Dentin: العاج</mark>', false)
            ->assertSee('المقطع المظلل هو مصدر إجابة السؤال.');
    }

    public function test_full_solving_flow_scores_and_links_back_to_the_passage(): void
    {
        $booklet = Booklet::factory()->create();
        $topic = Topic::factory()->for($booklet)->create();
        $block = ContentBlock::factory()->for($topic)->create(['data' => ['text' => 'الميتوكوندريا مصنع الطاقة']]);
        $questions = Question::factory()->count(3)->forTopic($topic)->ministerial()->withOptions(4)->create();
        $questions[0]->anchors()->create(['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 13]);
        $student = User::factory()->create();
        $this->signIn($student);

        $this->get("/booklets/{$booklet->id}")->assertOk()->assertSee('MCQ وزاري')->assertSee('وضع الاختبار');

        $this->post("/booklets/{$booklet->id}/attempts", ['mode' => 'ministerial'])->assertRedirect();
        $attempt = Attempt::where('user_id', $student->id)->sole();

        $this->get("/attempts/{$attempt->id}")->assertOk()->assertSee('إرسال');

        // Autosave one answer, then change it on submit.
        $q = $questions[0];
        $wrong = $q->options()->where('is_correct', false)->value('id');
        $this->withCredentials()->postJson("/attempts/{$attempt->id}/answer", ['question_id' => $q->id, 'option_id' => $wrong])->assertOk();

        $this->post("/attempts/{$attempt->id}/submit", ['answers' => [
            $questions[0]->id => $questions[0]->correctOption->id,
            $questions[1]->id => $questions[1]->correctOption->id,
            $questions[2]->id => $questions[2]->options()->where('is_correct', false)->value('id'),
        ]])->assertRedirect("/attempts/{$attempt->id}");

        $this->assertSame(2, $attempt->fresh()->correct_count);

        $this->get("/attempts/{$attempt->id}")->assertOk()
            ->assertSee('67%')
            ->assertSee('اعرض في الملزمة')
            ->assertSee("hl={$block->uuid}%3A0%3A13", false);
    }

    public function test_quiz_and_review_modes(): void
    {
        $booklet = Booklet::factory()->create(['quiz_question_count' => 2]);
        Question::factory()->count(4)->forBooklet($booklet)->teacher()->difficulty(Difficulty::Hard)->withOptions(3)->create();
        $student = User::factory()->create();
        $this->signIn($student);

        $this->post("/booklets/{$booklet->id}/attempts", ['mode' => 'quiz'])->assertSessionHasErrors('difficulty');
        $this->post("/booklets/{$booklet->id}/attempts", ['mode' => 'quiz', 'difficulty' => 'hard'])->assertRedirect();
        $this->assertSame(2, Attempt::where('user_id', $student->id)->sole()->total_questions);

        $this->get("/booklets/{$booklet->id}/review/teacher")->assertOk()->assertSee('الشرح');
    }

    public function test_students_cannot_open_other_students_attempts(): void
    {
        $booklet = Booklet::factory()->create();
        Question::factory()->forBooklet($booklet)->teacher()->withOptions()->create();
        $other = User::factory()->create();
        $attempt = app(AttemptService::class)->start($other, AttemptMode::Teacher, $booklet);

        $this->signIn(User::factory()->create());
        $this->get("/attempts/{$attempt->id}")->assertForbidden();
        $this->post("/attempts/{$attempt->id}/submit")->assertForbidden();
    }

    public function test_account_page_lists_subscriptions_and_attempts(): void
    {
        $subject = Subject::factory()->ministerial()->create(['name' => 'التعويضات السنية']);
        $student = User::factory()->create();
        app(SubscriptionService::class)->activate($student, null, [$subject->id], now()->addMonth());
        $this->signIn($student);

        $this->get('/account')->assertOk()->assertSee('التعويضات السنية')->assertSee('تسجيل الخروج');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest('student');
    }
}
