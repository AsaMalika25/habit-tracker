<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\User;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StreakService $streakService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $today = CarbonImmutable::now($user->timezone);
        $todayString = $today->toDateString();
        $validated = $request->validate([
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:'.$today->startOfMonth()->toDateString(),
                'before_or_equal:'.$todayString,
            ],
        ]);
        $selectedDate = isset($validated['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['date'], $user->timezone)->startOfDay()
            : $today->startOfDay();
        $selectedDateString = $selectedDate->toDateString();

        $todayHabits = $user->habits()
            ->active()
            ->forDate($todayString)
            ->withExists(['logs as completed_today' => fn ($query) => $query->whereDate('log_date', $todayString)])
            ->oldest()
            ->get();
        $habits = $user->habits()
            ->active()
            ->forDate($selectedDateString)
            ->withExists(['logs as completed_on_date' => fn ($query) => $query->whereDate('log_date', $selectedDateString)])
            ->oldest()
            ->get();

        $totalHabits = $todayHabits->count();
        $completedToday = $todayHabits->where('completed_today', true)->count();
        $completionPercentage = $totalHabits > 0
            ? (int) round(($completedToday / $totalHabits) * 100)
            : 0;
        $calendar = $this->calendar($user->id, $today, $selectedDate);
        $streak = $this->streakService->recalculate($user, $today->startOfDay());

        return view('dashboard', [
            'user' => $user,
            'habits' => $habits,
            'groupedHabits' => $habits->groupBy('category'),
            'selectedDate' => $selectedDate,
            'selectedTotalHabits' => $habits->count(),
            'selectedCompleted' => $habits->where('completed_on_date', true)->count(),
            'totalHabits' => $totalHabits,
            'completedToday' => $completedToday,
            'completionPercentage' => $completionPercentage,
            'totalFulfilled' => $user->habitLogs()->count(),
            'streak' => $streak->current_streak,
            'streakStatus' => $this->streakService->status($streak, $today),
            'calendar' => $calendar,
            'perfectDays' => $calendar->where('is_complete', true)->count(),
            'monthRate' => $calendar->where('is_future', false)->avg('percentage') ?? 0,
            'today' => $today,
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function calendar(int $userId, CarbonImmutable $today, CarbonImmutable $selectedDate): Collection
    {
        $start = $today->startOfMonth();
        $end = $today->endOfMonth();
        $habitDates = Habit::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($legacyQuery) => $legacyQuery
                    ->whereNull('start_date')
                    ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])))
            ->get(['start_date', 'created_at'])
            ->map(fn (Habit $habit) => ($habit->start_date ?? $habit->created_at)->toDateString());
        $counts = HabitLog::query()
            ->where('user_id', $userId)
            ->whereHas('habit', fn ($query) => $query->where('is_active', true))
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(log_date) as log_day, COUNT(DISTINCT habit_id) as completed_count')
            ->groupByRaw('DATE(log_date)')
            ->pluck('completed_count', 'log_day');

        $days = collect();

        for ($blank = 1; $blank < $start->isoWeekday(); $blank++) {
            $days->push(['is_blank' => true]);
        }

        for ($day = 1; $day <= $end->day; $day++) {
            $date = $start->setDay($day);
            $completedCount = (int) ($counts[$date->toDateString()] ?? 0);
            $habitCount = $habitDates
                ->filter(fn (string $habitDate) => $habitDate === $date->toDateString())
                ->count();
            $percentage = $habitCount > 0
                ? min(100, (int) round(($completedCount / $habitCount) * 100))
                : 0;

            $days->push([
                'is_blank' => false,
                'day' => $day,
                'date' => $date->toDateString(),
                'is_today' => $date->isSameDay($today),
                'is_selected' => $date->isSameDay($selectedDate),
                'is_future' => $date->isAfter($today),
                'is_complete' => ! $date->isAfter($today) && $habitCount > 0 && $completedCount >= $habitCount,
                'completed_count' => $completedCount,
                'habit_count' => $habitCount,
                'percentage' => $percentage,
            ]);
        }

        return $days;
    }
}
