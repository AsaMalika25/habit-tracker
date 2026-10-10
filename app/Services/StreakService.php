<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserStreak;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class StreakService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_DORMANT = 'dormant';

    public function recalculate(User $user, CarbonImmutable $today): UserStreak
    {
        $existingStreak = $user->streak()->first();
        $logsQuery = $user->habitLogs()
            ->whereDate('log_date', '<=', $today->toDateString());

        if ($existingStreak?->reset_at) {
            $logsQuery->where('completed_at', '>', $existingStreak->reset_at);
        }

        /** @var Collection<int, CarbonImmutable> $activeDates */
        $activeDates = $logsQuery
            ->select('log_date')
            ->distinct()
            ->orderBy('log_date')
            ->pluck('log_date')
            ->map(fn (string $date): CarbonImmutable => CarbonImmutable::parse($date));

        $longestStreak = 0;
        $runningStreak = 0;
        $previousDate = null;

        foreach ($activeDates as $date) {
            $runningStreak = $previousDate?->addDay()->isSameDay($date)
                ? $runningStreak + 1
                : 1;
            $longestStreak = max($longestStreak, $runningStreak);
            $previousDate = $date;
        }

        $currentStreak = 0;
        $expectedDate = $today;

        foreach ($activeDates->reverse() as $date) {
            if ($date->isSameDay($expectedDate)) {
                $currentStreak++;
                $expectedDate = $expectedDate->subDay();

                continue;
            }

            if ($currentStreak === 0 && $date->isSameDay($today->subDay())) {
                $currentStreak = 1;
                $expectedDate = $date->subDay();

                continue;
            }

            break;
        }

        $streak = $existingStreak ?? $user->streak()->make();
        $streak->fill([
            'current_streak' => $currentStreak,
            'longest_streak' => max($existingStreak?->longest_streak ?? 0, $longestStreak),
            'last_active_date' => $activeDates->last()?->toDateString(),
        ]);

        if ($existingStreak || $activeDates->isNotEmpty()) {
            $streak->save();
        }

        return $streak;
    }

    public function status(UserStreak $streak, CarbonImmutable $today): string
    {
        if (! $streak->last_active_date) {
            return self::STATUS_DORMANT;
        }

        $lastActiveDate = CarbonImmutable::parse(
            $streak->last_active_date->toDateString(),
            $today->timezone,
        );

        return $lastActiveDate->isBefore($today->startOfDay()->subDay())
            ? self::STATUS_FROZEN
            : self::STATUS_ACTIVE;
    }

    public function reset(User $user, CarbonImmutable $resetAt): UserStreak
    {
        $streak = $user->streak()->firstOrNew();
        $streak->fill([
            'current_streak' => 0,
            'longest_streak' => $streak->longest_streak ?? 0,
            'last_active_date' => null,
            'reset_at' => $resetAt,
        ]);
        $streak->save();

        return $streak;
    }
}
