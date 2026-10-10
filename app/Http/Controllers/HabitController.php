<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\ToggleHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Models\Habit;
use App\Models\User;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HabitController extends Controller
{
    public function __construct(
        private readonly StreakService $streakService,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $today = CarbonImmutable::now($user->timezone);
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today->toDateString()],
        ]);
        $selectedDate = isset($validated['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['date'], $user->timezone)->startOfDay()
            : $today->startOfDay();

        $habits = $user->habits()
            ->active()
            ->forDate($selectedDate->toDateString())
            ->withExists(['logs as completed_on_date' => fn ($query) => $query->whereDate('log_date', $selectedDate->toDateString())])
            ->latest()
            ->get();

        $todayHabits = $user->habits()
            ->active()
            ->forDate($today->toDateString());
        $sidebarTotalHabits = (clone $todayHabits)->count();
        $completedToday = (clone $todayHabits)
            ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $today->toDateString()))
            ->count();
        $streak = $this->streakService->recalculate($user, $today->startOfDay());

        return view('habits.index', [
            'user' => $user,
            'habits' => $habits,
            'groupedHabits' => $habits->groupBy('category'),
            'completedOnDate' => $habits->where('completed_on_date', true)->count(),
            'completedToday' => $completedToday,
            'sidebarTotalHabits' => $sidebarTotalHabits,
            'totalHabits' => $habits->count(),
            'streak' => $streak->current_streak,
            'streakStatus' => $this->streakService->status($streak, $today),
            'selectedDate' => $selectedDate,
            'today' => $today,
        ]);
    }

    public function store(StoreHabitRequest $request): RedirectResponse
    {
        $habit = $this->user($request)->habits()->create($request->validated());

        return to_route('habits.index', ['date' => $habit->start_date->toDateString()])
            ->with('status', 'Habit berhasil ditambahkan.');
    }

    public function update(UpdateHabitRequest $request, Habit $habit): RedirectResponse
    {
        $this->authorizeHabit($habit, $this->user($request));
        $habit->update($request->validated());

        return to_route('habits.index', ['date' => $habit->start_date->toDateString()])
            ->with('status', 'Habit berhasil diperbarui.');
    }

    public function toggle(ToggleHabitRequest $request, Habit $habit): RedirectResponse
    {
        $user = $this->authorizeHabit($habit, $this->user($request));
        $now = CarbonImmutable::now($user->timezone);
        $logDate = $request->validated('log_date');
        $existingLog = $habit->logs()->whereDate('log_date', $logDate)->first();
        $isFirstActivityToday = ! $existingLog
            && $logDate === $now->toDateString()
            && ! $user->habitLogs()->whereDate('log_date', $logDate)->exists();

        if ($existingLog) {
            return back()->with('status', 'Habit yang sudah selesai tidak dapat dibatalkan.');
        } else {
            $habit->logs()->create([
                'user_id' => $user->id,
                'log_date' => $logDate,
                'completed_at' => $now,
            ]);
            $message = 'Habit selesai untuk tanggal '.$logDate.'.';
        }

        $streak = $this->streakService->recalculate($user, $now->startOfDay());

        if ($isFirstActivityToday) {
            session()->flash('streak_popup', [
                'days' => $streak->current_streak,
                'title' => $streak->current_streak > 1 ? 'Streak berlanjut!' : 'Streak menyala!',
                'message' => $streak->current_streak > 1
                    ? 'Ritme hebatmu terus hidup. Kembali lagi besok!'
                    : 'Api pertamamu sudah menyala. Jaga ritmenya besok!',
            ]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        $user = $this->authorizeHabit($habit, $this->user($request));
        $habit->update(['is_active' => false]);
        $this->streakService->reset($user, CarbonImmutable::now($user->timezone));

        return to_route('habits.index')->with('status', 'Habit diarsipkan dan streak direset ke 0.');
    }

    private function authorizeHabit(Habit $habit, User $user): User
    {
        abort_unless($habit->user_id === $user->id, 404);

        return $user;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
