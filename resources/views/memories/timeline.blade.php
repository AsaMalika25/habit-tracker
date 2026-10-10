@extends('layouts.app')

@section('title', 'Timeline — Aura')

@section('content')
    <div class="timeline-page">
        <header class="timeline-hero">
            <div>
                <p class="memory-kicker"><i>history</i> Personal archive</p>
                <h1>Your timeline</h1>
                <p>Look back on completed rituals and reflections, gathered by the days they shaped.</p>
            </div>
            <div class="timeline-stats" aria-label="Timeline totals">
                <article><i>task_alt</i><span><strong>{{ $completedHabitCount }}</strong> rituals completed</span></article>
                <article><i>auto_stories</i><span><strong>{{ $journalCount }}</strong> journal entries</span></article>
                <article><i>calendar_month</i><span><strong>{{ $timeline->count() }}</strong> recorded days</span></article>
            </div>
        </header>

        <section class="timeline-feed" aria-labelledby="timelineHeading">
            <header class="timeline-heading">
                <div>
                    <p>Past days</p>
                    <h2 id="timelineHeading">Moments in your journey</h2>
                </div>
                <span>Newest first</span>
            </header>

            @forelse ($timeline as $day)
                @php($momentCount = $day['habits']->count() + $day['journals']->count())
                <article class="timeline-day">
                    <div class="timeline-marker" aria-hidden="true">
                        <time datetime="{{ $day['date']->toDateString() }}">{{ $day['date']->format('d') }}<small>{{ $day['date']->format('M Y') }}</small></time>
                        <i></i>
                    </div>
                    <div class="timeline-day-card">
                        <header>
                            <div><span>{{ $day['date']->format('l') }}</span><h3>{{ $day['date']->format('F j, Y') }}</h3></div>
                            <small>{{ $momentCount }} {{ Str::plural('moment', $momentCount) }}</small>
                        </header>
                        <div class="timeline-summary-grid">
                            <section>
                                <div class="timeline-summary-title"><i>task_alt</i><h4>Habits</h4><span>{{ $day['habits']->count() }} completed</span></div>
                                @forelse ($day['habits']->take(3) as $log)
                                    <div class="timeline-habit"><i>check</i><span><strong>{{ $log->habit->title }}</strong><small>{{ ucfirst($log->habit->category) }}</small></span></div>
                                @empty
                                    <p class="timeline-muted">No completed habits recorded.</p>
                                @endforelse
                                @if ($day['habits']->count() > 3)
                                    <a class="timeline-more" href="{{ route('habits.index', ['date' => $day['date']->toDateString()]) }}">+{{ $day['habits']->count() - 3 }} more <i>arrow_forward</i></a>
                                @endif
                            </section>
                            <section>
                                <div class="timeline-summary-title"><i>auto_stories</i><h4>Journal</h4><span>{{ $day['journals']->count() }} {{ Str::plural('entry', $day['journals']->count()) }}</span></div>
                                @forelse ($day['journals']->take(2) as $journal)
                                    <a class="timeline-journal" href="{{ route('journals.index', ['entry' => $journal]) }}">
                                        <span><strong>{{ $journal->title }}</strong><small>{{ $journal->mood ? ucfirst($journal->mood) : 'Reflection' }}</small></span>
                                        <p>{{ Str::limit($journal->content, 120) }}</p>
                                    </a>
                                @empty
                                    <p class="timeline-muted">No journal entry recorded.</p>
                                @endforelse
                                @if ($day['journals']->count() > 2)
                                    <span class="timeline-more">+{{ $day['journals']->count() - 2 }} more entries</span>
                                @endif
                            </section>
                        </div>
                    </div>
                </article>
            @empty
                <div class="memory-empty timeline-empty">
                    <span><i>history</i></span>
                    <h2>Your story starts with today</h2>
                    <p>Complete a habit or write a journal entry. It will appear here after the day has passed.</p>
                    <a class="primary-button" href="{{ route('habits.index') }}">Go to habits</a>
                </div>
            @endforelse
        </section>
    </div>
@endsection
