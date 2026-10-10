<header class="topbar">
    <button class="menu-button" id="menuButton" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><i>menu</i></button>
    <div class="daily-quote">
        <div><b>Today's Sanctuary</b><span></span><time>{{ now($user->timezone)->format('l, M j') }}</time></div>
        <p>“Small, continuous efforts compound into quiet mastery.”</p>
    </div>
    <div class="top-actions">
        <button class="icon-button notification" type="button" aria-label="Notifications"><i>notifications</i><span></span></button>
        <div class="profile"><img src="{{ asset('images/elena-vance.png') }}" alt="{{ $user->name }}"><span><b>{{ $user->name }}</b><small>Mindful Tier</small></span></div>
        <form method="POST" action="{{ route('logout') }}" data-submit-once>
            @csrf
            <button class="icon-button logout-button" type="submit" aria-label="Logout" title="Logout"><i>logout</i></button>
        </form>
    </div>
</header>
