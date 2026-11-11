<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\User;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    private const RANGE_LABELS = [
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        'all' => 'All time',
    ];

    public function __construct(
        private readonly StreakService $streakService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'range' => ['nullable', Rule::in(array_keys(self::RANGE_LABELS))],
        ]);
        $range = $validated['range'] ?? '30';
        $today = CarbonImmutable::now($user->timezone)->startOfDay();
        $periodStart = $range === 'all' ? null : $today->subDays(((int) $range) - 1);

        $habits = $this->habitsForPeriod($user, $periodStart, $today);
        $logsQuery = $user->habitLogs()
            ->with('habit:id,title,category,start_date,start_time,end_time')
            ->whereDate('log_date', '<=', $today->toDateString());
        $journalsQuery = $user->journals()
            ->whereDate('entry_date', '<=', $today->toDateString());

        if ($periodStart) {
            $logsQuery->whereDate('log_date', '>=', $periodStart->toDateString());
            $journalsQuery->whereDate('entry_date', '>=', $periodStart->toDateString());
        }

        $logs = $logsQuery->oldest('log_date')->get();
        $journals = $journalsQuery->oldest('entry_date')->get();
        $streak = $this->streakService->recalculate($user, $today);
        $todayHabits = $user->habits()->active()->forDate($today->toDateString());
        $sidebarTotalHabits = (clone $todayHabits)->count();
        $completedToday = (clone $todayHabits)
            ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $today->toDateString()))
            ->count();

        $scheduledCount = $habits->count();
        $completionCount = $logs->count();
        $opportunityCount = max($scheduledCount, $completionCount);
        $adherenceRate = $opportunityCount > 0
            ? min(100, (int) round(($completionCount / $opportunityCount) * 100))
            : 0;
        $focusMinutes = $logs->sum(fn ($log): int => $this->durationMinutes($log->habit));
        $journalWords = $journals->sum(
            fn ($journal): int => str_word_count(strip_tags((string) $journal->content)),
        );
        $averageMood = $journals->isNotEmpty()
            ? round((float) $journals->avg('mood_score'), 1)
            : null;

        $heatmap = $this->heatmap($habits, $logs, $periodStart, $today);
        $moods = $this->moodBreakdown($journals);
        $habitPerformance = $this->habitPerformance($habits, $logs);
        $topCategory = $logs
            ->groupBy(fn ($log): string => $log->habit?->category ?? 'other')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
        $currentStreak = (int) ($streak->current_streak ?? 0);
        $longestStreak = (int) ($streak->longest_streak ?? 0);
        $nextMilestone = max(7, ((int) floor($longestStreak / 7) + 1) * 7);

        return view('analytics.index', [
            'user' => $user,
            'range' => $range,
            'rangeLabel' => self::RANGE_LABELS[$range],
            'ranges' => self::RANGE_LABELS,
            'periodStart' => $periodStart,
            'today' => $today,
            'scheduledCount' => $scheduledCount,
            'completionCount' => $completionCount,
            'adherenceRate' => $adherenceRate,
            'focusMinutes' => $focusMinutes,
            'journalCount' => $journals->count(),
            'journalWords' => $journalWords,
            'averageMood' => $averageMood,
            'heatmap' => $heatmap,
            'moods' => $moods,
            'habitPerformance' => $habitPerformance,
            'hiddenHabitCount' => max(0, $habits->count() - $habitPerformance->count()),
            'topCategory' => $topCategory,
            'nextMilestone' => $nextMilestone,
            'daysToMilestone' => max(0, $nextMilestone - $longestStreak),
            'completedToday' => $completedToday,
            'sidebarTotalHabits' => $sidebarTotalHabits,
            'totalHabits' => $sidebarTotalHabits,
            'streak' => $currentStreak,
            'longestStreak' => $longestStreak,
            'streakStatus' => $this->streakService->status($streak, $today),
        ]);
    }

    /** @return Collection<int, Habit> */
    private function habitsForPeriod(User $user, ?CarbonImmutable $start, CarbonImmutable $today): Collection
    {
        return $user->habits()
            ->where(function (Builder $query) use ($start, $today): void {
                $query->where(function (Builder $dated) use ($start, $today): void {
                    $dated->whereNotNull('start_date')
                        ->whereDate('start_date', '<=', $today->toDateString());

                    if ($start) {
                        $dated->whereDate('start_date', '>=', $start->toDateString());
                    }
                })->orWhere(function (Builder $legacy) use ($start, $today): void {
                    $legacy->whereNull('start_date')
                        ->where('created_at', '<=', $today->endOfDay());

                    if ($start) {
                        $legacy->where('created_at', '>=', $start->startOfDay());
                    }
                });
            })
            ->oldest('start_date')
            ->oldest('created_at')
            ->get();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function heatmap(Collection $habits, Collection $logs, ?CarbonImmutable $periodStart, CarbonImmutable $today): Collection
    {
        $start = $today->subDays(27);

        if ($periodStart?->isAfter($start)) {
            $start = $periodStart;
        }

        $scheduledByDate = $habits->groupBy(
            fn (Habit $habit): string => ($habit->start_date ?? $habit->created_at)->toDateString(),
        );
        $completedByDate = $logs->groupBy(fn ($log): string => $log->log_date->toDateString());
        $days = collect();

        for ($date = $start; $date->lessThanOrEqualTo($today); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $scheduled = $scheduledByDate->get($dateString, collect())->count();
            $completed = $completedByDate->get($dateString, collect())->count();
            $opportunities = max($scheduled, $completed);
            $rate = $opportunities > 0 ? min(100, (int) round(($completed / $opportunities) * 100)) : 0;

            $days->push([
                'date' => $dateString,
                'label' => $date->format('D, M j'),
                'day' => $date->format('j'),
                'scheduled' => $scheduled,
                'completed' => $completed,
                'rate' => $rate,
                'level' => match (true) {
                    $rate >= 100 => 4,
                    $rate >= 67 => 3,
                    $rate >= 34 => 2,
                    $rate > 0 => 1,
                    default => 0,
                },
                'is_today' => $date->isSameDay($today),
            ]);
        }

        return $days;
    }

    /** @return Collection<int, array<string, int|string>> */
    private function moodBreakdown(Collection $journals): Collection
    {
        $catalog = [
            'positive' => ['label' => 'Positive', 'color' => 'primary', 'matches' => fn ($journal): bool => $journal->mood_score >= 4],
            'reflective' => ['label' => 'Reflective', 'color' => 'tertiary', 'matches' => fn ($journal): bool => $journal->mood_score === 3],
            'low' => ['label' => 'Low energy', 'color' => 'muted', 'matches' => fn ($journal): bool => $journal->mood_score <= 2],
        ];
        $total = $journals->count();

        return collect($catalog)->map(function (array $mood, string $key) use ($journals, $total): array {
            $count = $journals->filter($mood['matches'])->count();

            return [
                'key' => $key,
                'label' => $mood['label'],
                'color' => $mood['color'],
                'count' => $count,
                'percentage' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        })->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function habitPerformance(Collection $habits, Collection $logs): Collection
    {
        $logsByHabit = $logs->groupBy('habit_id');
        $categoryMeta = [
            'health' => ['label' => 'Health & Vitality', 'icon' => 'directions_walk'],
            'productivity' => ['label' => 'Focus & Mastery', 'icon' => 'target'],
            'mindfulness' => ['label' => 'Mindfulness', 'icon' => 'self_improvement'],
        ];

        return $habits
            ->sortByDesc(fn (Habit $habit) => ($habit->start_date ?? $habit->created_at)->getTimestamp())
            ->take(8)
            ->map(function (Habit $habit) use ($logsByHabit, $categoryMeta): array {
                $habitLogs = $logsByHabit->get($habit->id, collect());
                $duration = $this->durationMinutes($habit);
                $meta = $categoryMeta[$habit->category] ?? ['label' => ucfirst($habit->category), 'icon' => 'task_alt'];

                return [
                    'habit' => $habit,
                    'category_label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'date' => ($habit->start_date ?? $habit->created_at)->format('M j, Y'),
                    'time' => $habit->start_time && $habit->end_time
                        ? substr($habit->start_time, 0, 5).'–'.substr($habit->end_time, 0, 5)
                        : 'Flexible time',
                    'duration' => $duration > 0 ? $this->formatMinutes($duration) : 'Not set',
                    'completions' => $habitLogs->count(),
                    'adherence' => $habitLogs->isNotEmpty() ? 100 : 0,
                ];
            })
            ->values();
    }

    private function durationMinutes(?Habit $habit): int
    {
        if (! $habit?->start_time || ! $habit->end_time) {
            return 0;
        }

        $start = CarbonImmutable::parse($habit->start_time);
        $end = CarbonImmutable::parse($habit->end_time);

        return $end->isAfter($start) ? $start->diffInMinutes($end) : 0;
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $remainder > 0 ? $hours.'h '.$remainder.'m' : $hours.'h';
    }
}
