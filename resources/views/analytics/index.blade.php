@extends('layouts.app')

@section('title', 'Analytics & Insights — Aura')

@section('content')
    @php
        $positiveMood = $moods->firstWhere('key', 'positive')['percentage'] ?? 0;
        $reflectiveMood = $moods->firstWhere('key', 'reflective')['percentage'] ?? 0;
        $reflectiveEnd = $positiveMood + $reflectiveMood;
        $moodChart = $journalCount > 0
            ? "conic-gradient(var(--primary) 0 {$positiveMood}%, var(--tertiary) {$positiveMood}% {$reflectiveEnd}%, #7b86a1 {$reflectiveEnd}% 100%)"
            : 'conic-gradient(var(--surface-highest) 0 100%)';
        $categoryLabels = [
            'health' => 'Health & Vitality',
            'productivity' => 'Focus & Mastery',
            'mindfulness' => 'Mindfulness',
        ];
    @endphp

    <div class="analytics-page">
        <header class="analytics-header">
            <div>
                <p class="analytics-kicker"><span></span> Analytics &amp; intentional cadence · {{ $rangeLabel }}</p>
                <h1>Mindful Analytics &amp; Rhythm Resonance</h1>
                <p>See how your rituals, focused time, and journal reflections move together.</p>
            </div>
            <nav class="analytics-ranges" aria-label="Analytics period">
                @foreach ($ranges as $value => $label)
                    <a class="{{ $range === $value ? 'active' : '' }}" href="{{ route('analytics', ['range' => $value]) }}" @if ($range === $value) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </header>

        <section class="analytics-kpis" aria-label="Analytics summary">
            <article class="analytics-kpi">
                <div><span>Habit adherence</span><i>monitoring</i></div>
                <strong>{{ $adherenceRate }}<small>%</small></strong>
                <p>{{ $completionCount }} of {{ max($scheduledCount, $completionCount) }} scheduled completions</p>
                <div class="analytics-progress"><span style="width: {{ $adherenceRate }}%"></span></div>
            </article>
            <article class="analytics-kpi">
                <div><span>Focused ritual time</span><i>hourglass_top</i></div>
                <strong>
                    @if ($focusMinutes >= 60)
                        {{ number_format($focusMinutes / 60, 1) }}<small>hrs</small>
                    @else
                        {{ $focusMinutes }}<small>min</small>
                    @endif
                </strong>
                <p>Calculated from completed habit schedules</p>
            </article>
            <article class="analytics-kpi">
                <div><span>Journal volume</span><i>edit_note</i></div>
                <strong>{{ number_format($journalWords) }}<small>words</small></strong>
                <p>{{ $journalCount }} {{ Str::plural('reflection', $journalCount) }} · {{ $averageMood ? $averageMood.'/5 average mood' : 'No mood data yet' }}</p>
            </article>
            <article class="analytics-kpi analytics-kpi--accent">
                <div><span>Longest cadence streak</span><i>local_fire_department</i></div>
                <strong>{{ $longestStreak }}<small>days</small></strong>
                <p>{{ $daysToMilestone }} days to the {{ $nextMilestone }}-day milestone</p>
            </article>
        </section>

        <section class="analytics-overview">
            <article class="analytics-panel consistency-panel">
                <header class="analytics-panel-header">
                    <div><p>Daily rhythm</p><h2>Ritual Consistency Matrix</h2></div>
                    <span>{{ $heatmap->count() }} latest days</span>
                </header>
                <p class="analytics-panel-copy">Completion strength for each day inside the selected period.</p>
                <div class="analytics-heatmap" aria-label="Daily habit completion heatmap">
                    @foreach ($heatmap as $day)
                        <span class="level-{{ $day['level'] }} {{ $day['is_today'] ? 'today' : '' }}" title="{{ $day['label'] }}: {{ $day['completed'] }} of {{ max($day['scheduled'], $day['completed']) }} completed" aria-label="{{ $day['label'] }}, {{ $day['rate'] }} percent complete">
                            <b>{{ $day['day'] }}</b>
                        </span>
                    @endforeach
                </div>
                <footer class="analytics-legend">
                    <span>Rest day</span><i></i><i></i><i></i><i></i><i></i><span>Full cadence</span>
                </footer>
                <div class="analytics-mini-stats">
                    <article><i>event_available</i><span><small>Scheduled rituals</small><strong>{{ $scheduledCount }}</strong></span></article>
                    <article><i>task_alt</i><span><small>Completed rituals</small><strong>{{ $completionCount }}</strong></span></article>
                    <article><i>today</i><span><small>Today</small><strong>{{ $completedToday }}/{{ $sidebarTotalHabits }}</strong></span></article>
                </div>
            </article>

            <article class="analytics-panel mood-panel">
                <header class="analytics-panel-header">
                    <div><p>Journal resonance</p><h2>Emotional Equilibrium</h2></div>
                    <span>{{ $journalCount }} entries</span>
                </header>
                <p class="analytics-panel-copy">A simple distribution of the moods you selected in your reflections.</p>
                <div class="mood-summary">
                    <div class="mood-ring" style="background: {{ $moodChart }}" role="img" aria-label="Journal mood distribution">
                        <span><strong>{{ $averageMood ? round(($averageMood / 5) * 100) : 0 }}%</strong><small>mood score</small></span>
                    </div>
                    <div class="mood-bars">
                        @foreach ($moods as $mood)
                            <div class="mood-bar mood-bar--{{ $mood['color'] }}">
                                <p><span><i></i>{{ $mood['label'] }}</span><b>{{ $mood['percentage'] }}%</b></p>
                                <div><span style="width: {{ $mood['percentage'] }}%"></span></div>
                                <small>{{ $mood['count'] }} {{ Str::plural('entry', $mood['count']) }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="analytics-note">
                    <i>psychology_alt</i>
                    <p>
                        @if ($journalCount > 0)
                            Your selected moods average <strong>{{ $averageMood }}/5</strong> across {{ $journalCount }} reflections in this period.
                        @else
                            Add a journal reflection to begin seeing your emotional rhythm here.
                        @endif
                    </p>
                </div>
            </article>
        </section>

        <section class="analytics-panel cadence-panel">
            <header class="analytics-panel-header">
                <div><p>Habit detail</p><h2>Ritual Cadence Breakdown</h2></div>
                <span>{{ $scheduledCount }} rituals in period</span>
            </header>
            <p class="analytics-panel-copy">Schedule, time investment, and completion status for each ritual.</p>

            <div class="cadence-list">
                @forelse ($habitPerformance as $item)
                    <article class="cadence-row">
                        <div class="cadence-identity">
                            <i>{{ $item['icon'] }}</i>
                            <span><strong>{{ $item['habit']->title }}</strong><small>{{ $item['category_label'] }} · {{ $item['date'] }} · {{ $item['time'] }}</small></span>
                        </div>
                        <dl>
                            <div><dt>Completion</dt><dd>{{ $item['completions'] > 0 ? 'Completed' : 'Not completed' }}</dd></div>
                            <div><dt>Time invested</dt><dd>{{ $item['completions'] > 0 ? $item['duration'] : '—' }}</dd></div>
                            <div><dt>Adherence</dt><dd>{{ $item['adherence'] }}%</dd></div>
                            <div class="cadence-score"><dt>Goal cadence</dt><dd><span><i style="width: {{ $item['adherence'] }}%"></i></span></dd></div>
                        </dl>
                    </article>
                @empty
                    <div class="analytics-empty"><i>insights</i><h3>No ritual data in this period</h3><p>Add or complete a habit to start building your cadence report.</p><a class="primary-button" href="{{ route('habits.index') }}">Open Habits</a></div>
                @endforelse
            </div>
            @if ($hiddenHabitCount > 0)
                <p class="cadence-more">Showing the 8 latest rituals · {{ $hiddenHabitCount }} more included in the totals above.</p>
            @endif
        </section>

        <section class="analytics-panel discoveries-panel">
            <header class="analytics-panel-header">
                <div><p>Evidence from your records</p><h2>Habit Rhythm Discoveries</h2></div>
                <span>Updated from live data</span>
            </header>
            <div class="discoveries-grid">
                <article><i>wb_twilight</i><span><small>Strongest category</small><h3>{{ $topCategory ? ($categoryLabels[$topCategory] ?? ucfirst($topCategory)) : 'Waiting for completions' }}</h3><p>{{ $topCategory ? 'This category has the most completed rituals in the selected period.' : 'Complete a ritual to reveal your strongest category.' }}</p></span></article>
                <article><i>auto_stories</i><span><small>Reflection rhythm</small><h3>{{ $journalCount }} {{ Str::plural('journal day', $journalCount) }}</h3><p>{{ $journalWords > 0 ? number_format($journalWords).' words captured across your reflections.' : 'Your journal patterns will appear after your first entry.' }}</p></span></article>
                <article><i>verified</i><span><small>Next milestone</small><h3>{{ $nextMilestone }}-day cadence</h3><p>{{ $daysToMilestone }} more active {{ Str::plural('day', $daysToMilestone) }} beyond your longest streak.</p></span></article>
            </div>
        </section>
    </div>
@endsection
