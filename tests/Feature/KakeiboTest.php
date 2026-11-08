<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KakeiboTest extends TestCase
{
    use RefreshDatabase;

    /** The owner is the first user in the database. */
    private function owner(): User
    {
        return User::query()->oldest('id')->first() ?? User::factory()->create();
    }

    public function test_opens_without_login_and_creates_the_owner(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->get('/')->assertRedirect('/months/'.now()->format('Y-m'));
        $this->get('/months/'.now()->format('Y-m'))->assertOk()->assertSee('دفتر كاكيبو');

        $this->assertDatabaseCount('users', 1);
        $this->get('/login')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_month_page_renders_without_a_plan(): void
    {
        $this->get('/months/2026-11')
            ->assertOk()
            ->assertSee('تشرين الثاني')
            ->assertSee('لم تُكتب بعد');
    }

    public function test_invalid_month_is_404(): void
    {
        $this->get('/months/2026-13')->assertNotFound();
    }

    public function test_plan_and_reflection_are_saved(): void
    {
        $this->put('/months/2026-11/plan', [
            'income' => 1000000, 'fixed_costs' => 300000, 'savings_goal' => 200000, 'intention' => 'هدوء',
        ])->assertRedirect('/months/2026-11');

        $this->put('/months/2026-11/reflection', ['reflection' => 'جيد', 'improvement' => 'أقل مطاعم'])
            ->assertRedirect();

        $month = $this->owner()->months()->sole();
        $this->assertSame(500000.0, $month->available());
        $this->assertSame('أقل مطاعم', $month->improvement);

        $this->get('/months/2026-11')->assertSee('500,000');
    }

    public function test_entries_can_be_added_edited_and_deleted(): void
    {
        $this->post('/entries', [
            'spent_on' => '2026-11-03', 'category' => 'wants', 'description' => 'شاي ماتشا', 'amount' => 7500,
        ])->assertRedirect('/months/2026-11');

        $entry = $this->owner()->entries()->sole();
        $this->get('/months/2026-11')->assertSee('شاي ماتشا')->assertSee('7,500');
        $this->get('/months/2026-11?category=needs')->assertDontSee('شاي ماتشا');

        $this->put("/entries/{$entry->id}", [
            'spent_on' => '2026-11-04', 'category' => 'culture', 'description' => 'حفل موسيقى', 'amount' => 20000, 'note' => 'رائع',
        ])->assertRedirect("/entries/{$entry->id}");
        $this->assertSame('culture', $entry->fresh()->category->value);

        $this->delete("/entries/{$entry->id}")->assertRedirect('/months/2026-11');
        $this->assertDatabaseCount('entries', 0);
    }

    public function test_entry_validation(): void
    {
        $this->post('/entries', ['spent_on' => 'x', 'category' => 'luxury', 'description' => '', 'amount' => -5])
            ->assertSessionHasErrors(['spent_on', 'category', 'description', 'amount']);
    }

    public function test_comments_can_be_added_and_deleted(): void
    {
        $entry = Entry::factory()->for($this->owner())->create();

        $this->post("/entries/{$entry->id}/comments", ['body' => 'هل كان ضرورياً؟'])->assertRedirect();
        $comment = $entry->comments()->sole();

        $this->get("/entries/{$entry->id}")->assertOk()->assertSee('هل كان ضرورياً؟');

        $this->delete("/comments/{$comment->id}")->assertRedirect();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_entries_of_other_users_stay_hidden(): void
    {
        $this->owner();
        $stranger = User::factory()->create();
        $entry = Entry::factory()->for($stranger)->create(['description' => 'سرّي']);

        $this->get("/entries/{$entry->id}")->assertForbidden();
        $this->delete("/entries/{$entry->id}")->assertForbidden();
        $this->get('/months/'.$entry->spent_on->format('Y-m'))->assertDontSee('سرّي');
    }

    public function test_year_view_and_settings(): void
    {
        Entry::factory()->for($this->owner())->create(['spent_on' => '2026-03-10', 'amount' => 12000]);

        $this->get('/months?year=2026')->assertOk()->assertSee('آذار')->assertSee('12,000');

        $this->put('/settings', ['name' => 'يوكي', 'currency' => '¥'])->assertRedirect();
        $this->assertSame('¥', $this->owner()->fresh()->currency);
    }
}
