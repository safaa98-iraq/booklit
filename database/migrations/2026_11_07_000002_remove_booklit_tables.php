<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time cleanup for databases that still hold the old Booklit platform.
 *
 * Detected by the Booklit-only `users.role` column. Drops every Booklit table
 * (and their users, who were students/staff of the old site), then rebuilds
 * the users and kakeibo tables from the current migrations. Fresh databases
 * are left untouched.
 */
return new class extends Migration
{
    private const BOOKLIT_TABLES = [
        'attempt_answers', 'attempts', 'question_options', 'questions', 'passage_anchors',
        'content_blocks', 'topic_progress', 'favorites', 'user_highlights', 'topics',
        'booklets', 'editor_subject', 'subscriptions', 'subjects', 'stages', 'devices',
        'settings', 'personal_access_tokens',
    ];

    /** Tables rebuilt with the new schema (children first). */
    private const REBUILT_TABLES = [
        'comments', 'entries', 'months', 'sessions', 'password_reset_tokens', 'users',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        foreach ([...self::BOOKLIT_TABLES, ...self::REBUILT_TABLES] as $table) {
            $this->drop($table);
        }

        foreach (['0001_01_01_000000_create_users_table', '2026_11_07_000001_create_kakeibo_tables'] as $name) {
            (require database_path("migrations/{$name}.php"))->up();
        }

        // Forget migrations whose files no longer exist (the Booklit ones).
        $current = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'));
        DB::table('migrations')->whereNotIn('migration', $current->all())->delete();

        // The cache table may hold sessions/rate-limits of the old site.
        if (Schema::hasTable('cache')) {
            DB::table('cache')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the Booklit tables are gone for good.
    }

    private function drop(string $table): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS "'.$table.'" CASCADE');

            return;
        }

        Schema::withoutForeignKeyConstraints(fn () => Schema::dropIfExists($table));
    }
};
