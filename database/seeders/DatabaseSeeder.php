<?php

namespace Database\Seeders;

use App\Enums\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Demo notebook: demo@kakeibo.test / password, with this month's plan and some entries. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@kakeibo.test'],
            ['name' => 'سارة', 'password' => 'password', 'currency' => 'د.ع'],
        );

        if ($user->entries()->exists()) {
            return;
        }

        $today = Carbon::today();
        $user->months()->create([
            'year' => $today->year,
            'month' => $today->month,
            'income' => 1500000,
            'fixed_costs' => 450000,
            'savings_goal' => 300000,
            'intention' => 'أطبخ في البيت أكثر، وأشتري كتاباً واحداً فقط هذا الشهر.',
        ]);

        $rows = [
            [1, Category::Needs, 'خضار وفواكه', 32000, null],
            [2, Category::Needs, 'بنزين', 25000, null],
            [3, Category::Wants, 'عشاء مع الأصدقاء', 45000, 'كان جميلاً، لكن مرة واحدة في الشهر تكفي.'],
            [5, Category::Culture, 'رواية «كافكا على الشاطئ»', 18000, null],
            [6, Category::Needs, 'دواء', 12000, null],
            [8, Category::Unexpected, 'تصليح إطار السيارة', 30000, null],
            [9, Category::Wants, 'قهوة مختصة', 8000, null],
        ];

        foreach ($rows as [$day, $category, $description, $amount, $note]) {
            $date = $today->copy()->startOfMonth()->addDays(min($day, $today->day) - 1);
            $entry = $user->entries()->create([
                'spent_on' => $date->toDateString(),
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'note' => $note,
            ]);

            if ($category === Category::Wants && $note) {
                $entry->comments()->make(['body' => 'هل أسعدني بقدر ما كلّف؟ نعم، لكن سأختار مكاناً أبسط المرة القادمة.'])
                    ->user()->associate($user)->save();
            }
        }
    }
}
