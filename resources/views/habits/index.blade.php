@extends('layouts.app')

@section('title', 'Habits — Aura')

@section('content')
    <section class="welcome-panel habits-hero">
        <div class="welcome-copy">
            <div class="eyebrow-row"><span class="status-pill"><em></em> Habit workspace</span><span>•</span><time>{{ now($user->timezone)->format('l, M j') }}</time></div>
            <h1>Build your daily rhythm</h1>
            <p>Kelola dan centang habit untuk <b>{{ $selectedDate->format('l, M j, Y') }}</b>.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="flash-message" role="status"><i>check_circle</i>{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash-message error-message" role="alert"><i>error</i>{{ $errors->first() }}</div>
    @endif

    <section class="metric-grid habit-metrics" aria-label="Habit metrics">
        <article class="metric-card"><div><small>Active habits</small><i>format_list_bulleted</i></div><p><strong>{{ $totalHabits }}</strong> Habits</p><span>Your current daily ritual list</span></article>
        <article class="metric-card"><div><small>Completed on {{ $selectedDate->format('M j') }}</small><i>task_alt</i></div><p><strong>{{ $completedOnDate }}</strong> Done</p><div class="progress"><span style="width: {{ $totalHabits > 0 ? round(($completedOnDate / $totalHabits) * 100) : 0 }}%"></span></div></article>
        <article class="metric-card streak-metric streak-metric--{{ $streakStatus }}"><div><small>{{ $streakStatus === 'frozen' ? 'Frozen streak' : 'Current streak' }}</small><i>{{ $streakStatus === 'frozen' ? 'ac_unit' : 'local_fire_department' }}</i></div><p><strong>{{ $streak }}</strong> Days</p><span>{{ $streakStatus === 'frozen' ? 'Isi habit hari ini untuk menyalakannya lagi' : 'Keep your rhythm alive' }}</span></article>
    </section>

    <section class="habits-workspace">
        <form class="date-filter card" method="GET" action="{{ route('habits.index') }}">
            <div><span class="date-filter-icon"><i>calendar_month</i></span><label for="habitDate"><b>Habit date</b><small>Pilih tanggal untuk melihat atau mengubah checklist.</small></label></div>
            <div class="date-filter-controls">
                <input id="habitDate" name="date" type="date" value="{{ $selectedDate->toDateString() }}" max="{{ $today->toDateString() }}" required>
                <button class="primary-button" type="submit"><i>filter_alt</i> Filter Date</button>
                @if (! $selectedDate->isSameDay($today))
                    <a class="secondary-button" href="{{ route('habits.index') }}">Today</a>
                @endif
            </div>
        </form>

        <div class="habits-heading card">
            <div class="section-title"><span>Manage rituals · {{ $selectedDate->format('M j, Y') }}</span><h2>All Daily Habits</h2></div>
            <button class="primary-button" type="button" data-open-modal="#habitModal"><i>add</i> New Habit</button>
            <div class="filters" role="tablist">
                <button class="active" type="button" data-filter="all">All ({{ $totalHabits }})</button>
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

        @forelse ($categoryMeta as $category => $meta)
            @php
                $categoryHabits = $groupedHabits->get($category, collect());
            @endphp
            @if ($categoryHabits->isNotEmpty())
                <div class="habit-group" data-category="{{ $category }}">
                    <div class="group-heading {{ $meta['heading'] }}"><span><i>{{ $meta['icon'] }}</i> {{ $meta['label'] }}</span><small>{{ $categoryHabits->where('completed_on_date', true)->count() }}/{{ $categoryHabits->count() }} Completed</small></div>
                    @foreach ($categoryHabits as $habit)
                        <article class="habit-row manage {{ $habit->completed_on_date ? 'complete' : '' }}">
                            @if ($habit->completed_on_date)
                                <span class="habit-check readonly locked" aria-label="Habit selesai dan terkunci" title="Habit yang sudah selesai tidak dapat dibatalkan"><i>check</i></span>
                            @else
                                <form method="POST" action="{{ route('habits.toggle', $habit) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input name="log_date" type="hidden" value="{{ $selectedDate->toDateString() }}">
                                    <button class="habit-check" type="submit" aria-label="Centang habit untuk {{ $selectedDate->toDateString() }}"><i>check</i></button>
                                </form>
                            @endif
                            <div><b>{{ $habit->title }}</b><small>{{ $habit->completed_on_date ? 'Selesai pada '.$selectedDate->format('M j') : 'Belum selesai pada '.$selectedDate->format('M j') }}@if ($habit->start_time && $habit->end_time) &middot; {{ substr($habit->start_time, 0, 5) }} to {{ substr($habit->end_time, 0, 5) }}@endif</small></div>
                            <span class="streak {{ $habit->completed_on_date ? '' : 'muted' }}"><i>{{ $habit->completed_on_date ? 'task_alt' : 'schedule' }}</i>{{ $habit->completed_on_date ? 'Done' : $selectedDate->format('M j') }}</span>
                            <em class="tag {{ $category === 'mindfulness' ? 'mindful' : $category }}">{{ ucfirst($category) }}</em>
                            @unless ($habit->completed_on_date)
                                <div class="row-actions">
                                    <button type="button" class="row-action" data-open-modal="#editHabit-{{ $habit->id }}" aria-label="Edit {{ $habit->title }}"><i>edit</i></button>
                                    <form method="POST" action="{{ route('habits.destroy', $habit) }}" data-confirm="Arsipkan habit ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action danger" aria-label="Archive {{ $habit->title }}"><i>delete</i></button>
                                    </form>
                                </div>
                            @endunless
                        </article>
                    @endforeach
                </div>
            @endif
        @empty
        @endforelse

        @if ($habits->isEmpty())
            <div class="empty-state card"><i>add_task</i><h2>Belum ada habit</h2><p>Buat habit pertama Anda untuk mulai membangun ritme harian.</p><button class="primary-button" type="button" data-open-modal="#habitModal"><i>add</i> Create first habit</button></div>
        @endif
    </section>
@endsection

@section('modals')
    <div class="modal" id="habitModal" aria-hidden="true">
        <button class="modal-backdrop" type="button" data-close-modal aria-label="Close dialog"></button>
        <form class="modal-card" method="POST" action="{{ route('habits.store') }}" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            @csrf
            <button class="modal-close" type="button" data-close-modal aria-label="Close"><i>close</i></button>
            <span class="modal-icon"><i>spa</i></span><small>Create a mindful ritual</small><h2 id="modalTitle">New Habit</h2>
            <label>Ritual name<input id="newHabitName" name="title" type="text" value="{{ old('title') }}" maxlength="120" placeholder="e.g. Read for 20 minutes" required></label>
            <label>Category<select id="newHabitCategory" name="category" required><option value="health">Health &amp; Vitality</option><option value="productivity">Focus &amp; Mastery</option><option value="mindfulness">Mindfulness</option></select></label>
            <label>Start date<input name="start_date" type="date" value="{{ old('start_date', $selectedDate->toDateString()) }}" max="{{ $today->toDateString() }}" required></label>
            <div class="habit-time-fields">
                <label>Start time<input name="start_time" type="time" value="{{ old('start_time') }}"></label>
                <label>End time<input name="end_time" type="time" value="{{ old('end_time') }}"></label>
            </div>
            <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>Cancel</button><button class="primary-button" type="submit"><i>add</i>Create Ritual</button></div>
        </form>
    </div>

    @foreach ($habits->where('completed_on_date', false) as $habit)
        <div class="modal" id="editHabit-{{ $habit->id }}" aria-hidden="true">
            <button class="modal-backdrop" type="button" data-close-modal aria-label="Close dialog"></button>
            <form class="modal-card" method="POST" action="{{ route('habits.update', $habit) }}" role="dialog" aria-modal="true" aria-labelledby="editTitle-{{ $habit->id }}">
                @csrf
                @method('PUT')
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i>close</i></button>
                <span class="modal-icon"><i>edit</i></span><small>Refine your ritual</small><h2 id="editTitle-{{ $habit->id }}">Edit Habit</h2>
                <label>Ritual name<input name="title" type="text" value="{{ $habit->title }}" maxlength="120" required></label>
                <label>Category<select name="category" required><option value="health" @selected($habit->category === 'health')>Health &amp; Vitality</option><option value="productivity" @selected($habit->category === 'productivity')>Focus &amp; Mastery</option><option value="mindfulness" @selected($habit->category === 'mindfulness')>Mindfulness</option></select></label>
                <label>Start date<input name="start_date" type="date" value="{{ $habit->start_date?->toDateString() ?? $habit->created_at->toDateString() }}" max="{{ $today->toDateString() }}" required></label>
                <div class="habit-time-fields">
                    <label>Start time<input name="start_time" type="time" value="{{ $habit->start_time ? substr($habit->start_time, 0, 5) : '' }}"></label>
                    <label>End time<input name="end_time" type="time" value="{{ $habit->end_time ? substr($habit->end_time, 0, 5) : '' }}"></label>
                </div>
                <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>Cancel</button><button class="primary-button" type="submit"><i>save</i>Save Changes</button></div>
            </form>
        </div>
    @endforeach
@endsection
