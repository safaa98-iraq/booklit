<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private function fillNotebook(): void
    {
        $this->put('/settings', ['name' => 'هانا', 'currency' => '¥']);
        $this->put('/months/2026-11/plan', ['income' => 1000000, 'fixed_costs' => 300000, 'savings_goal' => 200000, 'intention' => 'هدوء']);
        $this->post('/entries', ['spent_on' => '2026-11-02', 'category' => 'culture', 'description' => 'كتاب', 'amount' => 20000, 'note' => 'رواية']);
        $entry = User::first()->entries()->sole();
        $this->post("/entries/{$entry->id}/comments", ['body' => 'يستحق']);
    }

    public function test_backup_round_trip_restores_everything(): void
    {
        $this->fillNotebook();

        $json = $this->get('/backup')->assertOk()->assertDownload('kakeibo-'.now()->format('Y-m-d').'.json')->streamedContent();
        $data = json_decode($json, true);
        $this->assertSame('kakeibo-backup', $data['format']);
        $this->assertSame('كتاب', $data['entries'][0]['description']);
        $this->assertSame('يستحق', $data['entries'][0]['comments'][0]['body']);

        // Lose everything, then restore.
        $this->delete('/entries/'.User::first()->entries()->sole()->id);
        $this->put('/settings', ['name' => 'x', 'currency' => 'x']);
        $this->post('/entries', ['spent_on' => '2026-10-01', 'category' => 'wants', 'description' => 'سيُستبدل', 'amount' => 1]);

        $file = UploadedFile::fake()->createWithContent('kakeibo.json', $json);
        $this->post('/backup/restore', ['backup' => $file])->assertRedirect('/settings')->assertSessionHas('status');

        $user = User::first();
        $this->assertSame('¥', $user->currency);
        $this->assertSame(['كتاب'], $user->entries()->pluck('description')->all());
        $this->assertSame('يستحق', $user->entries()->sole()->comments()->sole()->body);
        $this->assertSame(500000.0, $user->months()->sole()->available());
    }

    public function test_restore_rejects_other_files_and_keeps_data(): void
    {
        $this->fillNotebook();

        $this->post('/backup/restore', ['backup' => UploadedFile::fake()->createWithContent('x.json', '{"hello":1}')])
            ->assertSessionHasErrors('backup');
        $bad = json_encode(['format' => 'kakeibo-backup', 'entries' => [['spent_on' => 'x', 'category' => 'nope', 'description' => '', 'amount' => -1]]]);
        $this->post('/backup/restore', ['backup' => UploadedFile::fake()->createWithContent('b.json', $bad)])
            ->assertSessionHasErrors('backup');

        $this->assertSame(1, User::first()->entries()->count());
    }

    public function test_csv_export(): void
    {
        $this->fillNotebook();

        $csv = $this->get('/backup/csv')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('2026-11-02,الثقافة,كتاب,20000,رواية,1', $csv);
    }

    public function test_app_files_are_served(): void
    {
        $this->get('/months/2026-11')->assertSee('manifest.webmanifest', false)->assertSee('data-draft="new-entry"', false);
        $this->assertFileExists(public_path('sw.js'));
        $this->assertJson(file_get_contents(public_path('manifest.webmanifest')));
    }
}
