<?php

namespace Tests\Feature\Admin;

use App\Enums\AttemptMode;
use App\Filament\Pages\QuestionErrorRates;
use App\Filament\Pages\Settings;
use App\Filament\Pages\TopicPerformance;
use App\Filament\Resources\StudentResource\Pages\EditStudent;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Models\Booklet;
use App\Models\Question;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\DeviceBindingService;
use App\Services\ReportService;
use App\Services\SettingsService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_activates_subjects_with_expiry_from_the_students_table(): void
    {
        $student = User::factory()->create();
        $subject = Subject::factory()->ministerial()->create();

        Livewire::test(ListStudents::class)
            ->callTableAction('activateSubscription', $student, data: [
                'scope' => 'subjects',
                'subject_ids' => [$subject->id],
                'expires_at' => now()->addMonths(3)->toDateString(),
                'amount' => 25000,
            ])
            ->assertHasNoTableActionErrors();

        $subscription = $student->subscriptions()->sole();
        $this->assertSame($subject->id, $subscription->subject_id);
        $this->assertSame($this->admin->id, $subscription->activated_by);
        $this->assertTrue($subscription->expires_at->isSameDay(now()->addMonths(3)));
        $this->assertFalse(app(SubscriptionService::class)->isLocked($student, $subject));
    }

    public function test_admin_activates_a_whole_stage_from_the_edit_page(): void
    {
        $student = User::factory()->create();
        $stage = Stage::factory()->ministerial()->create();
        $subject = Subject::factory()->for($stage)->create();

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->callAction('activateSubscription', data: [
                'scope' => 'stage',
                'stage_id' => $stage->id,
                'expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($stage->id, $student->subscriptions()->sole()->stage_id);
        $this->assertFalse(app(SubscriptionService::class)->isLocked($student, $subject));
    }

    public function test_admin_resets_device_binding(): void
    {
        $student = User::factory()->create();
        app(DeviceBindingService::class)->bind($student, ['device_id' => 'old-device-123']);
        $student->createToken('old-device-123');

        Livewire::test(ListStudents::class)
            ->assertTableActionVisible('resetDevice', $student)
            ->callTableAction('resetDevice', $student);

        $this->assertNull($student->fresh()->activeDevice);
        $this->assertSame(0, $student->tokens()->count());
    }

    public function test_settings_page_saves_telegram_link_and_fourth_button(): void
    {
        Livewire::test(Settings::class)
            ->fillForm([
                'telegram_url' => 'https://t.me/new_admin',
                'locked_subject_message' => 'مقفلة',
                'locked_subject_button' => 'راسلنا',
                'fourth_button' => ['enabled' => true, 'label' => 'فيديوهات', 'icon' => 'video', 'action' => 'url', 'value' => 'https://example.com/videos'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(SettingsService::class);
        $this->assertSame('https://t.me/new_admin', $settings->get('telegram_url'));
        $this->assertSame('فيديوهات', $settings->get('fourth_button.label'));
        $this->assertTrue($settings->get('fourth_button.enabled'));
    }

    public function test_reports_compute_error_rate_and_topic_performance(): void
    {
        $booklet = Booklet::factory()->create();
        $topic = Topic::factory()->for($booklet)->create();
        $hard = Question::factory()->forTopic($topic)->teacher()->withOptions(4)->create(['text' => 'سؤال صعب']);
        $easy = Question::factory()->forTopic($topic)->teacher()->withOptions(4)->create(['text' => 'سؤال سهل']);
        $students = User::factory()->count(2)->create();
        $service = app(AttemptService::class);

        foreach ($students as $student) {
            $attempt = $service->start($student, AttemptMode::Teacher, $booklet);
            $service->submit($attempt, [
                ['question_id' => $hard->id, 'option_id' => $hard->options()->where('is_correct', false)->value('id')],
                ['question_id' => $easy->id, 'option_id' => $easy->correctOption->id],
            ]);
        }

        Livewire::test(QuestionErrorRates::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$hard, $easy], inOrder: true);

        $rates = app(ReportService::class)->questionErrorRates()->orderByDesc('error_rate')->get();
        $this->assertSame([$hard->id, $easy->id], $rates->pluck('id')->all());
        $this->assertEquals([100, 0], $rates->pluck('error_rate')->map(fn ($r) => (float) $r)->all());
        $this->assertEquals([2, 2], $rates->pluck('answered_count')->all());

        $rows = app(ReportService::class)->topicPerformance()->get();
        $this->assertCount(2, $rows);
        $this->assertEquals(50, $rows->first()->accuracy);
        $this->assertEquals(2, $rows->first()->answered);

        Livewire::test(TopicPerformance::class)->assertSuccessful()->assertCountTableRecords(2);
    }
}
