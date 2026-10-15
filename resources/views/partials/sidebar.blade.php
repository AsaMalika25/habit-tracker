<aside class="sidebar" id="sidebar">
    <div>
        <a class="brand" href="{{ route('dashboard') }}" aria-label="Aura dashboard">
            <span class="brand-mark"><img src="{{ asset('images/aura-logo.png') }}" alt="Aura logo"></span>
            <span><strong>Aura</strong><small>Habits &amp; Journal</small></span>
        </a>
        <nav class="nav" aria-label="Main navigation">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i>space_dashboard</i><span>Dashboard</span></a>
            <a class="{{ request()->routeIs('habits.*') ? 'active' : '' }}" href="{{ route('habits.index') }}"><i>task_alt</i><span>Habits</span></a>
            <a class="{{ request()->routeIs('analytics') ? 'active' : '' }}" href="{{ route('analytics') }}"><i>insights</i><span>Analytics</span></a>
            <a class="{{ request()->routeIs('journals.*') ? 'active' : '' }}" href="{{ route('journals.index') }}">@include('partials.icons.book')<span>Journal</span></a>
            <details class="nav-group" @if (request()->routeIs('memories.*')) open @endif>
                <summary class="{{ request()->routeIs('memories.*') ? 'active' : '' }}"><i>photo_library</i><span>Memory</span><i class="nav-chevron">expand_more</i></summary>
                <div class="nav-submenu">
                    <a class="{{ request()->routeIs('memories.timeline') ? 'active' : '' }}" href="{{ route('memories.timeline') }}"><i>timeline</i><span>Timeline</span></a>
                    <a class="{{ request()->routeIs('memories.keepsakes') ? 'active' : '' }}" href="{{ route('memories.keepsakes') }}"><i>collections</i><span>Keepsakes</span></a>
                    <a class="{{ request()->routeIs('memories.photobooth') ? 'active' : '' }}" href="{{ route('memories.photobooth') }}"><i>photo_camera</i><span>Photobooth</span></a>
                </div>
            </details>
            <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i>tune</i><span>Settings</span></a>
        </nav>
    </div>
    @php
        $streakStatus = $streakStatus ?? 'dormant';
    @endphp
    <div class="streak-card streak-card--{{ $streakStatus }}" data-streak-status="{{ $streakStatus }}">
        <div>
            <span><i>{{ $streakStatus === 'frozen' ? 'ac_unit' : 'local_fire_department' }}</i> {{ $streakStatus === 'frozen' ? 'Streak beku' : 'Daily streak' }}</span>
            <b>{{ $streak }} Days</b>
        </div>
        @php
            $sidebarHabitCount = $sidebarTotalHabits ?? $totalHabits;
            $sidebarPercentage = $sidebarHabitCount > 0 ? round(($completedToday / $sidebarHabitCount) * 100) : 0;
        @endphp
        <div class="progress"><span style="width: {{ $sidebarPercentage }}%"></span></div>
        <p>
            @if ($streakStatus === 'frozen')
                Api membeku karena satu hari terlewat. Isi habit hari ini untuk menyalakannya lagi.
            @else
                {{ $completedToday }} of {{ $sidebarHabitCount }} rituals fulfilled today
            @endif
        </p>
    </div>
</aside>
