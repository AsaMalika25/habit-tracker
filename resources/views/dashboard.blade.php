@extends('layouts.app')

@section('title', 'Aura — Habit & Journal Dashboard')

@section('content')
    <section class="welcome-panel">
        <div class="welcome-copy">
            <div class="eyebrow-row"><span class="status-pill"><em></em> Flow state active</span><span>•</span><time>{{ $today->format('l, M j') }}</time></div>
            <h1>Welcome back, {{ str($user->name)->before(' ') }}</h1>
            <p>You are currently in a {{ $streak }}-day flow rhythm. <b>{{ $completedToday }} of {{ $totalHabits }} rituals</b> are fulfilled for today.</p>
        </div>
        <div class="balance-card">
            @php
                $ringOffset = round(113.1 * (1 - ($completionPercentage / 100)), 1);
            @endphp
            <div class="progress-ring"><svg viewBox="0 0 44 44"><circle cx="22" cy="22" r="18"></circle><circle class="value" cx="22" cy="22" r="18" style="stroke-dashoffset: {{ $ringOffset }}"></circle></svg><b>{{ $completionPercentage }}%</b></div>
            <div><small>Today's balance</small><strong>{{ $completedToday }}/{{ $totalHabits }} <em>Habits</em></strong></div>
        </div>
    </section>

    <section class="metric-grid" aria-label="Today metrics">
        <article class="metric-card streak-metric streak-metric--{{ $streakStatus }}"><div><small>{{ $streakStatus === 'frozen' ? 'Frozen streak' : 'Current streak' }}</small><i>{{ $streakStatus === 'frozen' ? 'ac_unit' : 'local_fire_department' }}</i></div><p><strong>{{ $streak }}</strong> Days</p><span class="{{ $streakStatus === 'frozen' ? '' : 'positive' }}"><i>{{ $streakStatus === 'frozen' ? 'severe_cold' : 'trending_up' }}</i> {{ $streakStatus === 'frozen' ? 'Complete a habit to reignite it' : 'Keep the rhythm going' }}</span></article>
        <article class="metric-card"><div><small>Today completion</small><i>task_alt</i></div><p><strong>{{ $completionPercentage }}%</strong> Done</p><div class="progress"><span style="width:{{ $completionPercentage }}%"></span></div></article>
        <article class="metric-card"><div><small>Total fulfilled</small><i>auto_graph</i></div><p><strong>{{ $totalFulfilled }}</strong> Rituals</p><span class="positive"><i>arrow_upward</i> All recorded completions</span></article>
        <article class="metric-card"><div><small>Active habits</small><i>self_improvement</i></div><p><strong>{{ $totalHabits }}</strong> Habits</p><span><em class="dot"></em> Managed from the Habits menu</span></article>
    </section>

    <div class="dashboard-grid">
        <section class="habits-column" id="dashboardHabits">
            <div class="habits-heading card">
                <div class="section-title">
                    <span>{{ $selectedDate->isSameDay($today) ? 'Today' : $selectedDate->format('l, M j') }} &middot; {{ $selectedCompleted }}/{{ $selectedTotalHabits }} completed</span>
                    <h2>Daily Habits &amp; Rituals</h2>
                </div>
                @if (! $selectedDate->isSameDay($today))
                    <a class="dashboard-today-link" href="{{ route('dashboard') }}#dashboardHabits"><i>today</i> Today</a>
                @endif
                <div class="filters" role="tablist">
                    <button class="active" type="button" data-filter="all">All ({{ $selectedTotalHabits }})</button>
                    <button type="button" data-filter="health">Health ({{ $habits->where('category', 'health')->count() }})</button>
                    <button type="button" data-filter="productivity">Productivity ({{ $habits->where('category', 'productivity')->count() }})</button>
                    <button type="button" data-filter="mindfulness">Mindfulness ({{ $habits->where('category', 'mindfulness')->count() }})</button>
                </div>
            </div>

            @php
                $categoryMeta = [
                    'health' => ['label' => 'Health & Vitality', 'icon' => 'spa', 'heading' => ''],
                    'productivity' => ['label' => 'Focus & Mastery', 'icon' => 'bolt', 'heading' => 'focus'],
                    'mindfulness' => ['label' => 'Mindfulness & Reflection', 'icon' => 'self_improvement', 'heading' => 'mindful'],
                ];
            @endphp

            <div class="dashboard-habit-list" data-dashboard-habit-list>
            @foreach ($categoryMeta as $category => $meta)
                @php
                    $categoryHabits = $groupedHabits->get($category, collect());
                @endphp
                @if ($categoryHabits->isNotEmpty())
                    <div class="habit-group" data-category="{{ $category }}">
                        <div class="group-heading {{ $meta['heading'] }}"><span><i>{{ $meta['icon'] }}</i> {{ $meta['label'] }}</span><small>{{ $categoryHabits->where('completed_on_date', true)->count() }}/{{ $categoryHabits->count() }} Completed</small></div>
                        @foreach ($categoryHabits as $habit)
                            <article class="habit-row {{ $habit->completed_on_date ? 'complete' : '' }}">
                                <span class="habit-check readonly" aria-label="{{ $habit->completed_on_date ? 'Completed' : 'Not completed' }}"><i>check</i></span>
                                <div><b>{{ $habit->title }}</b><small>{{ $habit->completed_on_date ? 'Completed on '.$selectedDate->format('M j') : 'Pending on '.$selectedDate->format('M j').' — manage it from the Habits menu' }}</small></div>
                                <span class="streak {{ $habit->completed_on_date ? '' : 'muted' }}"><i>{{ $habit->completed_on_date ? 'task_alt' : 'schedule' }}</i>{{ $habit->completed_on_date ? 'Done' : $selectedDate->format('M j') }}</span>
                                <em class="tag {{ $category === 'mindfulness' ? 'mindful' : $category }}">{{ ucfirst($category) }}</em>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endforeach

            @if ($habits->isEmpty())
                <div class="empty-state card"><i>event_note</i><h2>No habits on this date</h2><p>No active ritual was found for {{ $selectedDate->format('M j, Y') }}.</p><a class="primary-button" href="{{ route('habits.index', ['date' => $selectedDate->toDateString()]) }}"><i>arrow_forward</i>Open Habits</a></div>
            @endif
            </div>
        </section>

        <aside class="insights-column" id="analytics">
            <section class="consistency card">
                <div class="consistency-header"><div class="section-title"><span>Monthly cadence</span><h2>{{ $today->format('F') }} Consistency</h2></div><div class="legend">Incomplete <i></i><i class="perfect"></i> Perfect</div></div>
                <div class="weekdays"><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span></div>
                <div class="heatmap" id="heatmap">
                    @foreach ($calendar as $day)
                        @if ($day['is_blank'])
                            <span class="calendar-blank" aria-hidden="true"></span>
                        @elseif ($day['is_future'])
                            <span class="future" title="Future date">{{ $day['day'] }}</span>
                        @else
                            <a
                                class="{{ $day['is_complete'] ? 'complete-day' : '' }} {{ $day['is_today'] ? 'today' : '' }} {{ $day['is_selected'] ? 'selected' : '' }}"
                                href="{{ route('dashboard', ['date' => $day['date']]) }}#dashboardHabits"
                                title="{{ $day['date'] }}: {{ $day['completed_count'] }}/{{ $day['habit_count'] }} habits completed"
                                aria-label="Show habits for {{ $day['date'] }}"
                                @if ($day['is_selected']) aria-current="date" @endif
                            >{{ $day['day'] }}</a>
                        @endif
                    @endforeach
                </div>
                <div class="micro-stats"><div><small>Perfect days this month</small><b>{{ $perfectDays }} Days</b></div><div><small>Month aggregate</small><b>{{ round($monthRate) }}% Rate</b></div></div>
            </section>

            <section class="dashboard-journal card" id="journal">
                <header class="dashboard-journal-header">
                    <div class="section-title"><span>Journal</span><h2>Latest Reflection</h2></div>
                    <a class="secondary-button" href="{{ route('journals.index') }}"><i>edit_note</i> Open Journal</a>
                </header>

                @if ($latestJournal)
                    <article class="dashboard-journal-entry">
                        <div class="dashboard-journal-meta">
                            <span><i>calendar_today</i>{{ $latestJournal->entry_date->format('M j, Y') }}</span>
                            <span><i>lock</i>{{ $latestJournal->published_at ? 'Archived' : 'Private draft' }}</span>
                        </div>
                        <h3>{{ $latestJournal->title }}</h3>
                        <p>{{ str($latestJournal->content)->squish()->limit(240) }}</p>
                        @if ($latestJournal->tags)
                            <div class="dashboard-journal-tags">
                                @foreach (array_slice($latestJournal->tags, 0, 3) as $tag)<span>#{{ $tag }}</span>@endforeach
                            </div>
                        @endif
                        <footer>
                            <span>{{ str_word_count(strip_tags($latestJournal->content)) }} words</span>
                            <a class="primary-button" href="{{ route('journals.index', ['entry' => $latestJournal]) }}">Read Entry</a>
                        </footer>
                    </article>
                @else
                    <div class="dashboard-journal-empty">
                        <i>auto_stories</i>
                        <h3>No journal entries yet</h3>
                        <p>Record a thought, lesson, or moment from today.</p>
                        <a class="primary-button" href="{{ route('journals.index') }}">Write First Entry</a>
                    </div>
                @endif
            </section>
        </aside>
    </div>
@endsection
