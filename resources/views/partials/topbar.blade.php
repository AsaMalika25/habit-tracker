<header class="topbar">
    <button class="menu-button" id="menuButton" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><i>menu</i></button>
    <div class="daily-quote">
        <div><b>Today's Sanctuary</b><span></span><time>{{ now($user->timezone)->format('l, M j') }}</time></div>
        <p>“Small, continuous efforts compound into quiet mastery.”</p>
    </div>
    <div class="top-actions">
        <button class="icon-button notification" type="button" aria-label="Notifications"><i>notifications</i><span></span></button>
        @php($profileInitials = Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode(''))
        <div class="profile">
            @if ($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
            @else
                <span class="profile-avatar" aria-hidden="true">{{ $profileInitials }}</span>
            @endif
            <span><b>{{ $user->name }}</b><small>{{ $user->roleLabel() }}</small></span>
        </div>
        <form method="POST" action="{{ route('logout') }}" data-submit-once>
            @csrf
            <button class="icon-button logout-button" type="submit" aria-label="Logout" title="Logout"><i>logout</i></button>
        </form>
    </div>
</header>
