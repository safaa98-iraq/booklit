<?php

namespace Database\Seeders;

use App\Enums\AttemptMode;
use App\Enums\Difficulty;
use App\Enums\UserRole;
use App\Models\Attempt;
use App\Models\Booklet;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\DeviceBindingService;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * Demo students (password: 12345678) with subscriptions in different states,
 * device bindings, and submitted attempts so every admin report has data.
 */
class DemoStudentsSeeder extends Seeder
{
    /** name, phone, skill (probability of a correct answer), plan */
    private const STUDENTS = [
        ['علي حسين كاظم', '07701234501', 0.85, 'stage5'],
        ['زينب محمد جاسم', '07701234502', 0.70, 'stage5'],
        ['حسن عبد الله ناصر', '07801234503', 0.55, 'surgery'],
        ['مريم أحمد عباس', '07801234504', 0.90, 'expired'],
        ['مصطفى كريم حميد', '07501234505', 0.40, 'none'],
        ['فاطمة علي رضا', '07501234506', 0.65, 'none'],
        ['يوسف سعد مهدي', '07901234507', 0.75, 'periodontics'],
        ['نور الهدى صادق', '07901234508', 0.60, 'none'],
    ];

    public function run(AttemptService $attempts, SubscriptionService $subscriptions, DeviceBindingService $devices): void
    {
        if (User::where('phone', self::STUDENTS[0][1])->exists()) {
            $this->command?->warn('Demo students already exist — skipped.');

            return;
        }

        mt_srand(77);
        $admin = User::where('role', UserRole::Admin)->first();
        $stage5 = Stage::where('name', 'المرحلة الخامسة')->first();

        foreach (self::STUDENTS as $n => [$name, $phone, $skill, $plan]) {
            $student = User::create([
                'name' => $name,
                'phone' => $phone,
                'password' => '12345678',
                'role' => UserRole::Student,
                'is_active' => true,
                'last_login_at' => now()->subHours(mt_rand(1, 200)),
                'created_at' => now()->subDays(30 - $n * 3),
            ]);

            // Subscriptions in different states.
            match ($plan) {
                'stage5' => $stage5 && $subscriptions->activate($student, $stage5->id, [], now()->addMonths(8), $admin, amount: 50000, notes: 'دفع عبر زين كاش'),
                'surgery' => $subscriptions->activate($student, null, Subject::where('name', 'جراحة الفم')->pluck('id')->all(), now()->addMonths(4), $admin, amount: 15000),
                'periodontics' => $subscriptions->activate($student, null, Subject::where('name', 'أمراض اللثة')->pluck('id')->all(), now()->addMonths(4), $admin, amount: 15000),
                'expired' => $stage5 && $subscriptions->activate($student, $stage5->id, [], now()->subDays(5), $admin, startsAt: now()->subMonths(3), amount: 50000),
                default => null,
            };

            // Most students have already signed in on a phone.
            if ($n < 6) {
                $devices->bind($student, [
                    'device_id' => 'demo-device-'.$phone,
                    'device_name' => ['Samsung Galaxy A54', 'iPhone 13', 'Redmi Note 12', 'iPhone 15', 'Galaxy S23', 'Oppo A78'][$n],
                    'platform' => $n % 2 ? 'ios' : 'android',
                ]);
            }

            $this->simulateAttempts($student, $skill, $attempts, $subscriptions);
        }
    }

    private function simulateAttempts(User $student, float $skill, AttemptService $service, SubscriptionService $subscriptions): void
    {
        $booklets = Booklet::with('subject.stage')->get()
            ->filter(fn (Booklet $b) => $subscriptions->canAccessSubject($student, $b->subject))
            ->shuffle()->take(mt_rand(3, 6));

        foreach ($booklets as $booklet) {
            $mode = [AttemptMode::Ministerial, AttemptMode::Teacher, AttemptMode::Quiz][mt_rand(0, 2)];
            $difficulty = $mode === AttemptMode::Quiz ? Difficulty::cases()[mt_rand(0, 2)] : null;

            try {
                $attempt = $service->start($student, $mode, $booklet, null, $difficulty);
            } catch (\Throwable) {
                continue; // e.g. no questions for that difficulty
            }

            $answers = $attempt->answers()->with('question.options')->get()->map(function ($row) use ($skill) {
                $correct = $row->question->options->firstWhere('is_correct', true);
                $wrong = $row->question->options->where('is_correct', false)->random();
                $roll = mt_rand() / mt_getrandmax();

                return [
                    'question_id' => $row->question_id,
                    'option_id' => $roll < 0.05 ? null : ($roll < $skill ? $correct->id : $wrong->id),
                ];
            })->all();

            $service->submit($attempt, $answers);

            $when = now()->subDays(mt_rand(0, 14))->subMinutes(mt_rand(0, 600));
            Attempt::whereKey($attempt->id)->update(['started_at' => $when->copy()->subMinutes(mt_rand(5, 25)), 'submitted_at' => $when]);
        }
    }
}
