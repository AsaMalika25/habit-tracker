<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Journal;
use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifeTrackDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_own_the_core_lifetrack_records(): void
    {
        $user = User::factory()->create();
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Morning meditation',
            'category' => 'mindfulness',
            'start_date' => '2026-09-21',
        ]);
        $log = HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => '2026-09-21',
            'completed_at' => '2026-09-21 07:30:00',
        ]);
        $journal = Journal::create([
            'user_id' => $user->id,
            'entry_date' => '2026-09-21',
            'content' => 'A calm and productive day.',
            'mood_score' => 5,
        ]);
        $streak = UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_active_date' => '2026-09-21',
        ]);

        $this->assertTrue($habit->is_active);
        $this->assertIsInt($habit->id);
        $this->assertIsInt($log->id);
        $this->assertSame($habit->id, $log->habit_id);
        $this->assertSame('2026-09-21', $habit->start_date->toDateString());
        $this->assertTrue($user->habits->contains($habit));
        $this->assertTrue($habit->logs->contains($log));
        $this->assertTrue($user->journals->contains($journal));
        $this->assertTrue($user->streak->is($streak));
        $this->assertIsInt($streak->id);
        $this->assertSame($user->id, $streak->user_id);
        $this->assertSame(5, $journal->mood_score);
        $this->assertSame('2026-09-21', $log->log_date->toDateString());
    }
}
