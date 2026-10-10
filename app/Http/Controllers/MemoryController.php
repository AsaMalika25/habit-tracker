<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemoryController extends Controller
{
    public function __construct(
        private readonly StreakService $streakService,
    ) {}

    public function photobooth(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $today = CarbonImmutable::now($user->timezone);
        $todayHabits = $user->habits()->active()->forDate($today->toDateString());
        $sidebarTotalHabits = (clone $todayHabits)->count();
        $completedToday = (clone $todayHabits)
            ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $today->toDateString()))
            ->count();
        $streak = $this->streakService->recalculate($user, $today->startOfDay());
        $photos = $user->photos();

        return view('memories.photobooth', [
            'user' => $user,
            'memories' => (clone $photos)->latest('captured_at')->limit(8)->get(),
            'memoryCount' => $photos->count(),
            'completedToday' => $completedToday,
            'sidebarTotalHabits' => $sidebarTotalHabits,
            'totalHabits' => $sidebarTotalHabits,
            'streak' => $streak->current_streak,
            'streakStatus' => $this->streakService->status($streak, $today),
        ]);
    }

    public function timeline(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $today = CarbonImmutable::now($user->timezone);
        // ponytail: in-memory grouping suits a personal timeline; paginate by date if histories become large.
        $habitLogs = $user->habitLogs()
            ->with('habit:id,title,category')
            ->whereDate('log_date', '<', $today->toDateString())
            ->latest('log_date')
            ->get();
        $journals = $user->journals()
            ->whereDate('entry_date', '<', $today->toDateString())
            ->latest('entry_date')
            ->latest('created_at')
            ->get();
        $habitDays = $habitLogs->groupBy(fn ($log) => $log->log_date->toDateString());
        $journalDays = $journals->groupBy(fn ($journal) => $journal->entry_date->toDateString());
        $timeline = $habitDays->keys()
            ->merge($journalDays->keys())
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn (string $date) => [
                'date' => CarbonImmutable::parse($date, $user->timezone),
                'habits' => $habitDays->get($date, collect()),
                'journals' => $journalDays->get($date, collect()),
            ]);

        $todayHabits = $user->habits()->active()->forDate($today->toDateString());
        $sidebarTotalHabits = (clone $todayHabits)->count();
        $completedToday = (clone $todayHabits)
            ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $today->toDateString()))
            ->count();
        $streak = $this->streakService->recalculate($user, $today->startOfDay());

        return view('memories.timeline', [
            'user' => $user,
            'timeline' => $timeline,
            'completedHabitCount' => $habitLogs->count(),
            'journalCount' => $journals->count(),
            'completedToday' => $completedToday,
            'sidebarTotalHabits' => $sidebarTotalHabits,
            'totalHabits' => $sidebarTotalHabits,
            'streak' => $streak->current_streak,
            'streakStatus' => $this->streakService->status($streak, $today),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg', 'max:5120'],
            'filter' => ['required', Rule::in(['original', 'noir', 'sepia', 'vivid', 'glow', 'collage'])],
        ]);
        $path = $request->file('photo')->store('photobooth', 'public');
        abort_if(! $path, 500, 'The photo could not be saved.');

        $user->photos()->create([
            'path' => $path,
            'filter' => $validated['filter'],
            'captured_at' => now(),
        ]);

        return to_route('memories.photobooth')->with('status', 'Your photobooth memory was saved.');
    }
}
