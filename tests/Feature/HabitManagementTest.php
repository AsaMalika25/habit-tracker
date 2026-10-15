<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\User;
use App\Models\UserStreak;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 08:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_create_update_and_archive_a_habit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('habits.index'))
            ->assertOk()
            ->assertDontSee('Quick Entry')
            ->assertDontSee('Search habits, notes...')
            ->assertSee('Start time')
            ->assertSee('End time')
            ->assertSee('New Habit');

        $this->actingAs($user)
            ->post(route('habits.store'), [
                'title' => 'Read for 20 minutes',
                'category' => 'productivity',
                'start_date' => '2026-09-21',
                'start_time' => '07:30',
                'end_time' => '08:15',
            ])
            ->assertRedirectToRoute('habits.index', ['date' => '2026-09-21']);

        $habit = Habit::query()->whereBelongsTo($user)->sole();
        $this->assertSame('Read for 20 minutes', $habit->title);
        $this->assertSame('07:30', substr($habit->start_time, 0, 5));
        $this->assertSame('08:15', substr($habit->end_time, 0, 5));

        UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 7,
            'longest_streak' => 12,
            'last_active_date' => '2026-09-21',
        ]);

        $this->actingAs($user)
            ->put(route('habits.update', $habit), [
                'title' => 'Read for 30 minutes',
                'category' => 'mindfulness',
                'start_date' => '2026-09-20',
                'start_time' => '19:00',
                'end_time' => '19:45',
            ])
            ->assertRedirectToRoute('habits.index', ['date' => '2026-09-20']);

        $this->assertDatabaseHas('habits', [
            'id' => $habit->id,
            'title' => 'Read for 30 minutes',
            'category' => 'mindfulness',
            'start_date' => '2026-09-20 00:00:00',
        ]);
        $habit->refresh();
        $this->assertSame('19:00', substr($habit->start_time, 0, 5));
        $this->assertSame('19:45', substr($habit->end_time, 0, 5));

        $this->actingAs($user)
            ->delete(route('habits.destroy', $habit))
            ->assertRedirectToRoute('habits.index');

        $this->assertDatabaseHas('habits', ['id' => $habit->id, 'is_active' => false]);
        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'current_streak' => 0,
            'longest_streak' => 12,
            'last_active_date' => null,
        ]);
        $this->assertNotNull($user->streak()->first()->reset_at);
    }

    public function test_toggle_is_persisted_and_dashboard_is_read_only(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Morning walk',
            'category' => 'health',
        ]);

        $this->actingAs($user)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-21'])
            ->assertRedirect();

        $this->assertTrue(
            $habit->logs()
                ->where('user_id', $user->id)
                ->whereDate('log_date', '2026-09-21')
                ->exists(),
        );

        $this->actingAs($user)
            ->get(route('habits.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Edit Morning walk"', false)
            ->assertDontSee('aria-label="Archive Morning walk"', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Morning walk')
            ->assertSee('1 of 1 rituals')
            ->assertSee('complete-day', false)
            ->assertDontSee('Quick Entry')
            ->assertDontSee('New Habit');

        $streakId = $user->streak()->value('id');
        $this->actingAs($user)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-21'])
            ->assertSessionHas('status', 'Habit yang sudah selesai tidak dapat dibatalkan.');
        $this->assertDatabaseHas('habit_logs', [
            'habit_id' => $habit->id,
            'log_date' => '2026-09-21 00:00:00',
        ]);
        $this->assertDatabaseCount('user_streaks', 1);
        $this->assertSame($streakId, $user->streak()->value('id'));

        $this->actingAs($user)
            ->get(route('habits.index'))
            ->assertOk()
            ->assertSee('aria-label="Habit selesai dan terkunci"', false)
            ->assertDontSee('aria-label="Centang habit untuk 2026-09-21"', false)
            ->assertDontSee('aria-label="Edit Morning walk"', false)
            ->assertDontSee('aria-label="Archive Morning walk"', false);
    }

    public function test_user_cannot_change_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::create([
            'user_id' => $owner->id,
            'title' => 'Private ritual',
            'category' => 'health',
        ]);

        $this->actingAs($otherUser)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-21'])
            ->assertNotFound();
    }

    public function test_selected_date_controls_checklist_and_dashboard_calendar(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habits = collect([
            Habit::create(['user_id' => $user->id, 'title' => 'Morning walk', 'category' => 'health', 'start_date' => '2026-09-20']),
            Habit::create(['user_id' => $user->id, 'title' => 'Read a book', 'category' => 'mindfulness', 'start_date' => '2026-09-20']),
        ]);
        Habit::create([
            'user_id' => $user->id,
            'title' => 'Started later',
            'category' => 'productivity',
            'start_date' => '2026-09-21',
        ]);
        $previousHabit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Previous-day habit',
            'category' => 'health',
            'start_date' => '2026-09-19',
        ]);

        $this->actingAs($user)->patch(route('habits.toggle', $previousHabit), [
            'log_date' => '2026-09-19',
        ]);

        foreach ($habits as $habit) {
            $this->actingAs($user)->patch(route('habits.toggle', $habit), [
                'log_date' => '2026-09-20',
            ]);
        }

        $this->actingAs($user)
            ->get(route('habits.index', ['date' => '2026-09-20']))
            ->assertOk()
            ->assertViewHas('completedOnDate', 2)
            ->assertSee('value="2026-09-20"', false)
            ->assertSee('Completed on Sep 20')
            ->assertDontSee('Started later');

        $this->actingAs($user)
            ->get(route('habits.index', ['date' => '2026-09-21']))
            ->assertOk()
            ->assertViewHas('totalHabits', 1)
            ->assertSee('Started later')
            ->assertDontSee('Morning walk')
            ->assertDontSee('Previous-day habit');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('title="2026-09-20: 2/2 habits completed"', false)
            ->assertSee('href="'.route('dashboard', ['date' => '2026-09-20']).'#dashboardHabits"', false)
            ->assertSee('complete-day', false)
            ->assertDontSee('Morning walk')
            ->assertDontSee('Previous-day habit')
            ->assertDontSee('href="'.route('dashboard', ['date' => '2026-09-22']), false);

        $this->actingAs($user)
            ->get(route('dashboard', ['date' => '2026-09-20']))
            ->assertOk()
            ->assertViewHas('selectedTotalHabits', 2)
            ->assertViewHas('selectedCompleted', 2)
            ->assertSee('aria-current="date"', false)
            ->assertSee('Completed on Sep 20')
            ->assertDontSee('Previous-day habit')
            ->assertDontSee('Started later');
    }

    public function test_future_date_cannot_be_checked(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Future ritual',
            'category' => 'health',
        ]);

        $this->actingAs($user)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-22'])
            ->assertSessionHasErrors('log_date');

        $this->assertDatabaseMissing('habit_logs', ['habit_id' => $habit->id]);

        $this->actingAs($user)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-20'])
            ->assertSessionHasErrors('log_date');

        $this->assertDatabaseMissing('habit_logs', [
            'habit_id' => $habit->id,
            'log_date' => '2026-09-20',
        ]);
    }

    public function test_first_check_in_today_opens_the_streak_celebration_once(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $firstHabit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Morning walk',
            'category' => 'health',
        ]);
        $secondHabit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Drink water',
            'category' => 'health',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('habits.toggle', $firstHabit), ['log_date' => '2026-09-21']);

        $response->assertSessionHas('streak_popup', fn (array $popup): bool => $popup['days'] === 1);
        $this->followRedirects($response)
            ->assertSee('data-streak-popup', false)
            ->assertSee('Streak menyala!');

        $this->actingAs($user)
            ->patch(route('habits.toggle', $secondHabit), ['log_date' => '2026-09-21'])
            ->assertSessionMissing('streak_popup');
    }

    public function test_streak_becomes_frozen_after_one_full_day_is_missed(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Morning walk',
            'category' => 'health',
            'start_date' => '2026-09-19',
        ]);

        $this->actingAs($user)
            ->patch(route('habits.toggle', $habit), ['log_date' => '2026-09-19']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('streak', 0)
            ->assertViewHas('streakStatus', 'frozen')
            ->assertSee('data-streak-status="frozen"', false)
            ->assertSee('Streak beku');

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'current_streak' => 0,
            'last_active_date' => '2026-09-19 00:00:00',
        ]);
    }

    public function test_habit_form_validates_its_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('habits.index'))
            ->post(route('habits.store'), [
                'title' => '',
                'category' => 'unknown',
                'start_date' => 'not-a-date',
                'start_time' => '09:00',
                'end_time' => '08:00',
            ])
            ->assertRedirect(route('habits.index'))
            ->assertSessionHasErrors(['title', 'category', 'end_time']);
    }
}
