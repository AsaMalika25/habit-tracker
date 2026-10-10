<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalRequest;
use App\Models\Journal;
use App\Models\User;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JournalController extends Controller
{
    private const MOODS = [
        'happy' => ['label' => 'Senang', 'emoji' => '😊', 'score' => 5],
        'sad' => ['label' => 'Sedih', 'emoji' => '😢', 'score' => 2],
        'worried' => ['label' => 'Khawatir', 'emoji' => '😟', 'score' => 2],
    ];

    public function __construct(
        private readonly StreakService $streakService,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $today = CarbonImmutable::now($user->timezone);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'mood' => ['nullable', Rule::in(array_keys(self::MOODS))],
            'scope' => ['nullable', Rule::in(['all', 'week', 'favorites', 'saved', 'photos'])],
            'entry' => ['nullable', 'integer'],
        ]);

        $entriesQuery = Journal::query()
            ->where('user_id', $user->id)
            ->latest('entry_date')
            ->latest('created_at');
        $this->applyFilters($entriesQuery, $filters, $today);
        $entries = $entriesQuery->get();

        $selectedEntry = isset($filters['entry'])
            ? $user->journals()->whereKey($filters['entry'])->firstOrFail()
            : null;

        $todayHabits = $user->habits()
            ->active()
            ->forDate($today->toDateString());
        $sidebarTotalHabits = (clone $todayHabits)->count();
        $completedToday = (clone $todayHabits)
            ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $today->toDateString()))
            ->count();
        $streak = $this->streakService->recalculate($user, $today->startOfDay());

        $linkedHabits = collect();
        if ($selectedEntry) {
            $linkedHabits = $user->habits()
                ->whereHas('logs', fn ($query) => $query->whereDate('log_date', $selectedEntry->entry_date->toDateString()))
                ->orderBy('title')
                ->get();
        }

        $prompts = [
            'What small gesture of grace or disciplined stillness brought you ease today?',
            'Which moment today deserves more attention than you first gave it?',
            'What did your habits reveal about the person you are becoming?',
            'Where did you find calm, focus, or gratitude today?',
        ];

        return view('journals.index', [
            'user' => $user,
            'today' => $today,
            'entries' => $entries,
            'selectedEntry' => $selectedEntry,
            'linkedHabits' => $linkedHabits,
            'moods' => self::MOODS,
            'prompt' => $prompts[$today->dayOfYear % count($prompts)],
            'archiveCount' => $user->journals()->count(),
            'streak' => $streak->current_streak,
            'streakStatus' => $this->streakService->status($streak, $today),
            'completedToday' => $completedToday,
            'sidebarTotalHabits' => $sidebarTotalHabits,
            'totalHabits' => $sidebarTotalHabits,
            'filters' => $filters,
        ]);
    }

    public function store(JournalRequest $request): RedirectResponse
    {
        $user = $this->user($request);
        $journal = $user->journals()->create($this->payload($request));

        return to_route('journals.index')
            ->with('status', $journal->published_at ? 'Reflection published to the sanctuary archive.' : 'Reflection saved.');
    }

    public function update(JournalRequest $request, Journal $journal): RedirectResponse
    {
        $this->authorizeJournal($journal, $this->user($request));
        $oldImageUrl = $journal->image_url;
        $journal->update($this->payload($request, $journal));
        if ($oldImageUrl !== $journal->image_url) {
            $this->deleteLocalImage($oldImageUrl);
        }

        return to_route('journals.index', ['entry' => $journal->id])
            ->with('status', $journal->published_at ? 'Reflection updated in the archive.' : 'Reflection saved.');
    }

    public function toggle(Request $request, Journal $journal, string $flag): RedirectResponse
    {
        $this->authorizeJournal($journal, $this->user($request));
        $column = $flag === 'favorite' ? 'is_favorite' : 'is_bookmarked';
        $journal->update([$column => ! $journal->{$column}]);

        return back()->with('status', ucfirst($flag).' updated.');
    }

    public function destroy(Request $request, Journal $journal): RedirectResponse
    {
        $this->authorizeJournal($journal, $this->user($request));
        $imageUrl = $journal->image_url;
        $journal->delete();
        $this->deleteLocalImage($imageUrl);

        return to_route('journals.index')->with('status', 'Reflection removed from the sanctuary.');
    }

    /** @param Builder<Journal> $query @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters, CarbonImmutable $today): void
    {
        if ($search = $filters['q'] ?? null) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($mood = $filters['mood'] ?? null) {
            $query->where('mood', $mood);
        }

        match ($filters['scope'] ?? 'all') {
            'week' => $query->whereBetween('entry_date', [
                $today->startOfWeek()->toDateString(),
                $today->endOfWeek()->toDateString(),
            ]),
            'favorites' => $query->where('is_favorite', true),
            'saved' => $query->where('is_bookmarked', true),
            'photos' => $query->whereNotNull('image_url'),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function payload(JournalRequest $request, ?Journal $journal = null): array
    {
        $validated = $request->validated();
        $mood = $validated['mood'];
        $imageUrl = $validated['image_url'] ?? null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('journals', 'public');
            abort_if(! $path, 500, 'The image could not be saved.');
            $imageUrl = Storage::disk('public')->url($path);
        }

        return [
            'entry_date' => $validated['entry_date'],
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'mood' => $mood,
            'mood_score' => self::MOODS[$mood]['score'],
            'tags' => collect(explode(',', $validated['tags'] ?? ''))
                ->map(fn (string $tag) => ltrim(trim($tag), '#'))
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all(),
            'image_url' => $imageUrl,
            'audio_url' => $validated['audio_url'] ?? null,
            'published_at' => ($validated['action'] ?? 'save') === 'publish'
                ? now()
                : $journal?->published_at,
        ];
    }

    private function deleteLocalImage(?string $imageUrl): void
    {
        $path = parse_url($imageUrl ?? '', PHP_URL_PATH);
        $host = parse_url($imageUrl ?? '', PHP_URL_HOST);
        $localHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (is_string($path) && ($host === null || $host === $localHost) && str_starts_with($path, '/storage/journals/')) {
            Storage::disk('public')->delete(substr($path, strlen('/storage/')));
        }
    }

    private function authorizeJournal(Journal $journal, User $user): void
    {
        abort_unless($journal->user_id === $user->id, 404);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
