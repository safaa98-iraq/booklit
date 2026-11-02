<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: creates the initial admin only.
     * Run `php artisan db:seed --class=DemoContentSeeder` for sample content.
     */
    public function run(): void
    {
        // firstOrCreate: runs on every deploy without resetting a changed admin password.
        User::firstOrCreate(
            ['email' => config('booklit.admin.email')],
            [
                'name' => config('booklit.admin.name'),
                'password' => config('booklit.admin.password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );
    }
}
