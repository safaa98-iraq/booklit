<?php

namespace Database\Seeders;

use App\Models\Booklet;
use App\Models\TopicProgress;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/** Reading history and favorites for demo students without any (idempotent). */
class DemoReadingSeeder extends Seeder
{
    public function run(SubscriptionService $access): void
    {
        mt_srand(4242);

        foreach (User::students()->whereDoesntHave('topicProgress')->where('phone', 'like', '07%01234%')->get() as $student) {
            $booklets = Booklet::published()->with(['subject.stage', 'topics'])->get()
                ->filter(fn ($b) => $access->canAccessSubject($student, $b->subject))
                ->shuffle()->take(mt_rand(2, 4));

            foreach ($booklets->values() as $n => $booklet) {
                foreach ($booklet->topics as $i => $topic) {
                    // Finish the first topic(s), leave the last one in progress.
                    $completed = $i < $booklet->topics->count() - 1 || $n === 0;
                    $when = now()->subDays(mt_rand(0, 10))->subMinutes(mt_rand(0, 600));
                    TopicProgress::create([
                        'user_id' => $student->id,
                        'topic_id' => $topic->id,
                        'booklet_id' => $booklet->id,
                        'scroll_percent' => $completed ? 100 : mt_rand(25, 70),
                        'reading_seconds' => mt_rand(240, 1500),
                        'completed_at' => $completed ? $when : null,
                        'last_read_at' => $when,
                    ]);
                }
                if ($n === 0) {
                    $student->favorites()->firstOrCreate(['favoritable_type' => 'booklet', 'favoritable_id' => $booklet->id]);
                    $student->favorites()->firstOrCreate(['favoritable_type' => 'topic', 'favoritable_id' => $booklet->topics->first()->id]);
                }
            }
        }
    }
}
