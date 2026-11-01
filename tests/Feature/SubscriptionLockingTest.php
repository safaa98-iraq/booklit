<?php

namespace Tests\Feature;

use App\Models\Booklet;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStudentApi;
use Tests\TestCase;

class SubscriptionLockingTest extends TestCase
{
    use InteractsWithStudentApi, RefreshDatabase;

    private function subscriptions(): SubscriptionService
    {
        return app(SubscriptionService::class);
    }

    public function test_free_subjects_are_open_to_every_registered_student(): void
    {
        $subject = Subject::factory()->create();
        $booklet = Booklet::factory()->for($subject)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->getJson("/api/v1/subjects/{$subject->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonPath('data.booklets.0.id', $booklet->id);

        $this->getJson("/api/v1/booklets/{$booklet->id}", $headers)->assertOk();
    }

    public function test_ministerial_subject_is_locked_and_returns_telegram_contact(): void
    {
        app(SettingsService::class)->set(['telegram_url' => 'https://t.me/booklit_admin']);
        $subject = Subject::factory()->ministerial()->create();
        $booklet = Booklet::factory()->for($subject)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->getJson('/api/v1/subjects', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.is_locked', true)
            ->assertJsonMissingPath('data.0.booklets');

        $this->getJson("/api/v1/subjects/{$subject->id}", $headers)
            ->assertForbidden()
            ->assertJsonPath('code', 'subject_locked')
            ->assertJsonPath('telegram_url', 'https://t.me/booklit_admin');

        $this->getJson("/api/v1/booklets/{$booklet->id}", $headers)
            ->assertForbidden()
            ->assertJsonPath('code', 'subject_locked');

        $this->postJson('/api/v1/attempts', ['booklet_id' => $booklet->id, 'mode' => 'ministerial'], $headers)
            ->assertForbidden()
            ->assertJsonPath('code', 'subject_locked');

        $this->getJson("/api/v1/booklets/{$booklet->id}/questions/review?source=ministerial", $headers)
            ->assertForbidden();
    }

    public function test_every_subject_of_a_ministerial_stage_is_locked(): void
    {
        $stage = Stage::factory()->ministerial()->create();
        $subject = Subject::factory()->for($stage)->create(['is_ministerial' => false]);
        $student = User::factory()->create();

        $this->assertTrue($this->subscriptions()->isLocked($student, $subject));
    }

    public function test_subject_subscription_unlocks_only_that_subject(): void
    {
        $stage = Stage::factory()->ministerial()->create();
        $biology = Subject::factory()->for($stage)->create();
        $chemistry = Subject::factory()->for($stage)->create();
        $admin = User::factory()->admin()->create();
        [$student, $headers] = $this->actingAsStudentOnDevice();

        $this->subscriptions()->activate($student, null, [$biology->id], now()->addMonth(), $admin);

        $this->getJson("/api/v1/subjects/{$biology->id}", $headers)->assertOk()->assertJsonPath('data.is_locked', false);
        $this->getJson("/api/v1/subjects/{$chemistry->id}", $headers)->assertForbidden();
    }

    public function test_stage_subscription_unlocks_all_subjects_of_the_stage(): void
    {
        $stage = Stage::factory()->ministerial()->create();
        $subjects = Subject::factory()->count(3)->for($stage)->create();
        $otherStageSubject = Subject::factory()->for(Stage::factory()->ministerial())->create();
        [$student, $headers] = $this->actingAsStudentOnDevice();

        $this->subscriptions()->activate($student, $stage->id, [], now()->addMonths(6));

        foreach ($subjects as $subject) {
            $this->getJson("/api/v1/subjects/{$subject->id}", $headers)->assertOk();
        }
        $this->getJson("/api/v1/subjects/{$otherStageSubject->id}", $headers)->assertForbidden();
    }

    public function test_expired_subscription_relocks_the_subject(): void
    {
        $subject = Subject::factory()->ministerial()->create();
        [$student, $headers] = $this->actingAsStudentOnDevice();

        $this->subscriptions()->activate($student, null, [$subject->id], now()->addDays(3));
        $this->getJson("/api/v1/subjects/{$subject->id}", $headers)->assertOk();

        $this->travel(4)->days();

        $this->getJson("/api/v1/subjects/{$subject->id}", $headers)->assertForbidden();
    }

    public function test_revoked_and_future_subscriptions_do_not_unlock(): void
    {
        $subject = Subject::factory()->ministerial()->create();
        $student = User::factory()->create();

        $future = $this->subscriptions()->activate($student, null, [$subject->id], now()->addMonths(2), startsAt: now()->addMonth())->first();
        $this->assertTrue(app(SubscriptionService::class)->isLocked($student, $subject));

        $active = $this->subscriptions()->activate($student, null, [$subject->id], now()->addMonth())->first();
        $this->assertFalse(app(SubscriptionService::class)->isLocked($student, $subject));

        $this->subscriptions()->revoke($active);
        $this->assertTrue(app(SubscriptionService::class)->isLocked($student, $subject));
        $this->assertNotNull($future->fresh());
    }

    public function test_staff_are_never_locked_out(): void
    {
        $subject = Subject::factory()->ministerial()->create();

        $this->assertFalse($this->subscriptions()->isLocked(User::factory()->admin()->create(), $subject));
        $this->assertFalse($this->subscriptions()->isLocked(User::factory()->editor()->create(), $subject));
    }

    public function test_home_listing_marks_locked_and_unlocked_subjects(): void
    {
        $stage = Stage::factory()->create();
        $free = Subject::factory()->for($stage)->create(['sort_order' => 1]);
        $paid = Subject::factory()->for($stage)->ministerial()->create(['sort_order' => 2]);
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->getJson('/api/v1/stages', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.subjects.0.id', $free->id)
            ->assertJsonPath('data.0.subjects.0.is_locked', false)
            ->assertJsonPath('data.0.subjects.1.id', $paid->id)
            ->assertJsonPath('data.0.subjects.1.is_locked', true);
    }

    public function test_settings_endpoint_exposes_telegram_link_publicly(): void
    {
        app(SettingsService::class)->set(['telegram_url' => 'https://t.me/someone']);

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.telegram_url', 'https://t.me/someone')
            ->assertJsonStructure(['data' => ['locked_subject_message', 'fourth_button' => ['enabled', 'label', 'action']]]);
    }
}
