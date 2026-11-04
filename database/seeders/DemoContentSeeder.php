<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Full demo: admin + editor, dentistry content (5 stages) and demo students.
 * Safe to run more than once — each part skips itself if already seeded.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        User::firstOrCreate(['email' => 'editor@example.com'], [
            'name' => 'محرر المحتوى',
            'password' => 'password',
            'role' => UserRole::Editor,
        ]);

        $this->call([DentistryContentSeeder::class, DemoTablesSeeder::class, DemoStudentsSeeder::class, DemoReadingSeeder::class]);
    }
}
