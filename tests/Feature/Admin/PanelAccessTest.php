<?php

namespace Tests\Feature\Admin;

use App\Models\Booklet;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_cannot_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $admin = User::factory()->admin()->create();
        $booklet = Booklet::factory()->create();
        $question = Question::factory()->forBooklet($booklet)->withOptions()->create();
        $student = User::factory()->create();

        $this->actingAs($admin);

        foreach ([
            '/admin', '/admin/stages', '/admin/subjects', '/admin/booklets', '/admin/booklets/create',
            "/admin/booklets/{$booklet->id}/edit", '/admin/questions', '/admin/questions/create',
            "/admin/questions/{$question->id}/edit", '/admin/students', "/admin/students/{$student->id}/edit",
            '/admin/editors', '/admin/attempts', '/admin/question-error-rates', '/admin/topic-performance',
            '/admin/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_editor_sees_only_content_of_assigned_subjects(): void
    {
        $editor = User::factory()->editor()->create();
        $mine = Subject::factory()->create();
        $other = Subject::factory()->create();
        $editor->assignedSubjects()->attach($mine);

        $myBooklet = Booklet::factory()->for($mine)->create();
        $otherBooklet = Booklet::factory()->for($other)->create();
        $myQuestion = Question::factory()->for($mine)->withOptions()->create();
        $otherQuestion = Question::factory()->for($other)->withOptions()->create();

        $this->actingAs($editor);

        $this->get('/admin/booklets')->assertOk()->assertSee($myBooklet->title)->assertDontSee($otherBooklet->title);
        $this->get("/admin/booklets/{$myBooklet->id}/edit")->assertOk();
        $this->get("/admin/booklets/{$otherBooklet->id}/edit")->assertNotFound();

        $this->get("/admin/questions/{$myQuestion->id}/edit")->assertOk();
        $this->get("/admin/questions/{$otherQuestion->id}/edit")->assertNotFound();

        foreach (['/admin/stages', '/admin/subjects', '/admin/students', '/admin/editors', '/admin/settings', '/admin/attempts', '/admin/question-error-rates'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_editor_without_subjects_cannot_create_content(): void
    {
        $this->actingAs(User::factory()->editor()->create());

        $this->get('/admin/booklets/create')->assertForbidden();
        $this->get('/admin/questions/create')->assertForbidden();
    }

    public function test_policies_follow_editor_subject_assignment(): void
    {
        $editor = User::factory()->editor()->create();
        $subject = Subject::factory()->create();
        $booklet = Booklet::factory()->for($subject)->create();
        $question = Question::factory()->for($subject)->create();

        $this->assertFalse($editor->can('update', $booklet));
        $this->assertFalse($editor->can('update', $question));

        $editor->assignedSubjects()->attach($subject);

        $this->assertTrue($editor->can('update', $booklet));
        $this->assertTrue($editor->can('delete', $question));
        $this->assertFalse($editor->can('viewAny', User::class));
        $this->assertTrue(User::factory()->admin()->create()->can('viewAny', User::class));
    }
}
