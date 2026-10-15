@extends('layouts.app')

@section('title', 'Journal Sanctuary — Aura')

@section('content')
    @php
        $activeScope = $filters['scope'] ?? 'all';
        $editingEntry = $selectedEntry && request()->boolean('edit') ? $selectedEntry : null;
        $formDate = old('entry_date', $editingEntry?->entry_date->toDateString() ?? $today->toDateString());
        $formMood = old('mood', $editingEntry?->mood);
        $formTags = old('tags', implode(', ', $editingEntry?->tags ?? []));
    @endphp

    <div class="journal-sanctuary">
        <header class="sanctuary-page-header">
            <div>
                <p class="sanctuary-kicker">Sanctuary Archive <span></span> {{ $archiveCount }} Entries Recorded</p>
                <h1>Mindful Journal &amp; Reflections</h1>
            </div>
            <form class="sanctuary-filters" method="GET" action="{{ route('journals.index') }}" data-journal-search-form>
                <label class="sanctuary-select"><i>tune</i><span class="sr-only">Filter mood</span>
                    <select name="mood" data-journal-auto-filter>
                        <option value="">All Moods</option>
                        @foreach ($moods as $key => $mood)
                            <option value="{{ $key }}" @selected(($filters['mood'] ?? '') === $key)>{{ $mood['emoji'] }} {{ $mood['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="sanctuary-search"><i>search</i><span class="sr-only">Search journal by title</span><input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search journal titles..." autocomplete="off" data-journal-search></label>
                <input name="scope" type="hidden" value="{{ $activeScope }}">
                @if ($archiveCount > 0 && ! $editingEntry)
                    <button class="primary-button" type="button" data-open-modal="#journalModal"><i>add</i> New Entry</button>
                @endif
            </form>
        </header>

        @if (session('status'))
            <div class="flash-message sanctuary-flash" role="status"><i>check_circle</i>{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash-message error-message sanctuary-flash" role="alert"><i>error</i>{{ $errors->first() }}</div>
        @endif

        <div class="sanctuary-canvas {{ $archiveCount > 0 ? 'has-entries' : '' }} {{ $selectedEntry ? 'has-selected' : '' }}">
            <aside class="sanctuary-archive" aria-label="Journal archive">
                <nav class="archive-scopes" aria-label="Journal filters">
                    @foreach ([
                        'all' => ['All', null],
                        'week' => ['This Week', null],
                        'favorites' => ['Favorites', 'heart'],
                        'saved' => ['Saved', 'bookmark'],
                        'photos' => ['Photo Notes', 'camera'],
                    ] as $scope => [$label, $icon])
                        <a class="{{ $activeScope === $scope ? 'active' : '' }}" href="{{ route('journals.index', array_filter(['scope' => $scope, 'mood' => $filters['mood'] ?? null, 'q' => $filters['q'] ?? null])) }}">
                            @if ($icon === 'heart')@include('partials.icons.heart')@endif
                            @if ($icon === 'bookmark')@include('partials.icons.bookmark')@endif
                            @if ($icon === 'camera')@include('partials.icons.camera')@endif
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <div class="archive-list">
                    @forelse ($entries as $entry)
                        @php($mood = $moods[$entry->mood] ?? ['label' => ucfirst($entry->mood), 'emoji' => '📝'])
                        <article class="archive-entry {{ $selectedEntry && $selectedEntry->is($entry) ? 'active' : '' }}">
                            <div class="archive-entry-meta">
                                <span>{{ $entry->entry_date->isToday() ? 'Today · '.$entry->created_at->format('h:i A') : $entry->entry_date->format('M j · Y') }}</span>
                                <span class="entry-mood">@if (($mood['icon'] ?? null) === 'ground')@include('partials.icons.ground')@else{{ $mood['emoji'] }}@endif {{ $mood['label'] }}</span>
                            </div>
                            <a class="archive-entry-link" href="{{ route('journals.index', array_filter(['entry' => $selectedEntry && $selectedEntry->is($entry) ? null : $entry->id, 'scope' => $activeScope, 'mood' => $filters['mood'] ?? null, 'q' => $filters['q'] ?? null])) }}">
                                <h2>{{ $entry->title }}</h2>
                                <p>{{ str($entry->content)->squish()->limit(145) }}</p>
                            </a>
                            <div class="archive-entry-footer">
                                <div class="entry-tags">
                                    @foreach (array_slice($entry->tags ?? [], 0, 2) as $tag)<span>#{{ $tag }}</span>@endforeach
                                </div>
                                <div class="entry-signals">
                                    <span>{{ str_word_count(strip_tags($entry->content)) }} words</span>
                                    @if ($entry->image_url)<span class="entry-attachment" title="Photo attached">@include('partials.icons.image')</span>@endif
                                    @if ($entry->audio_url)<span class="entry-attachment" title="Audio attached">@include('partials.icons.microphone')</span>@endif
                                    <form method="POST" action="{{ route('journals.toggle', [$entry, 'favorite']) }}">
                                        @csrf @method('PATCH')
                                        <button class="entry-icon {{ $entry->is_favorite ? 'active' : '' }}" type="submit" aria-label="Toggle favorite">@include('partials.icons.heart')</button>
                                    </form>
                                    <form method="POST" action="{{ route('journals.toggle', [$entry, 'bookmark']) }}">
                                        @csrf @method('PATCH')
                                        <button class="entry-icon {{ $entry->is_bookmarked ? 'active' : '' }}" type="submit" aria-label="Toggle bookmark">@include('partials.icons.bookmark')</button>
                                    </form>
                                    <form method="POST" action="{{ route('journals.destroy', $entry) }}" data-confirm="Remove this reflection permanently?">
                                        @csrf @method('DELETE')
                                        <button class="entry-icon entry-delete" type="submit" aria-label="Delete {{ $entry->title }}" title="Delete reflection"><i>delete</i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="archive-empty">
                            @include('partials.icons.book')<h2>No reflections yet</h2><p>Create a quiet space for today's thoughts.</p>
                        </div>
                    @endforelse
                </div>

                @if ($entries->isNotEmpty())
                    <div class="archive-count"><i>history</i>{{ $entries->count() }} memories shown</div>
                @endif
            </aside>

            @if ($archiveCount === 0 || $selectedEntry)
            <section class="sanctuary-workspace" aria-label="Journal editor">
                @if ($archiveCount === 0 || $editingEntry)
                <form id="journalEditor" class="journal-editor-form" method="POST" action="{{ $editingEntry ? route('journals.update', $editingEntry) : route('journals.store') }}" enctype="multipart/form-data">
                    @csrf
                    @if ($editingEntry) @method('PUT') @endif
                    @include('journals.partials.editor-fields')
                </form>

                @elseif ($selectedEntry)
                    @php($selectedMood = $moods[$selectedEntry->mood] ?? ['label' => ucfirst($selectedEntry->mood), 'emoji' => '📝'])
                    <article class="journal-detail">
                        <div class="editor-meta-bar">
                            <span>@include('partials.icons.calendar') {{ $selectedEntry->entry_date->format('l, M j, Y') }}</span>
                            <span><i>schedule</i>{{ $selectedEntry->created_at->format('h:i A') }}</span>
                            <span class="meta-spacer"></span>
                            <span><i>cloud</i>{{ $selectedEntry->published_at ? 'Archived' : 'Private draft' }}</span>
                            <span>@include('partials.icons.timer')<b>{{ max(1, (int) ceil(str_word_count(strip_tags($selectedEntry->content)) / 200)) }} min read</b></span>
                        </div>

                        <header class="journal-detail-header">
                            <span class="entry-mood">{{ $selectedMood['emoji'] }} {{ $selectedMood['label'] }}</span>
                            <h2>{{ $selectedEntry->title }}</h2>
                            <span class="title-underline"></span>
                        </header>

                        <div class="journal-detail-content">{!! nl2br(e($selectedEntry->content)) !!}</div>

                        @if ($selectedEntry->tags)
                            <div class="journal-detail-tags">
                                @foreach ($selectedEntry->tags as $tag)<span>#{{ $tag }}</span>@endforeach
                            </div>
                        @endif

                        @if ($linkedHabits->isNotEmpty())
                            <div class="linked-rituals">
                                <div><span>Completed Rituals Anchored to This Note</span><b>{{ $linkedHabits->count() }} Logged</b></div>
                                <ul>@foreach ($linkedHabits as $habit)<li><i>check_circle</i>{{ $habit->title }}</li>@endforeach</ul>
                            </div>
                        @endif

                        @if ($selectedEntry->image_url || $selectedEntry->audio_url)
                            <div class="journal-media-grid">
                                @if ($selectedEntry->image_url)
                                    <figure><img src="{{ $selectedEntry->image_url }}" alt="Journal attachment"><figcaption><i>photo_camera</i> Sanctuary image</figcaption></figure>
                                @endif
                                @if ($selectedEntry->audio_url)
                                    <div class="audio-note"><span><i>graphic_eq</i></span><div><b>Voice reflection</b><audio controls preload="metadata" src="{{ $selectedEntry->audio_url }}"></audio></div></div>
                                @endif
                            </div>
                        @endif

                        <footer class="journal-detail-footer">
                            <span><i>check_circle</i>Saved {{ $selectedEntry->updated_at->diffForHumans() }} · {{ str_word_count(strip_tags($selectedEntry->content)) }} words</span>
                            <div>
                                <a class="journal-edit-button" href="{{ route('journals.index', array_filter(['entry' => $selectedEntry->id, 'edit' => 1, 'scope' => $activeScope, 'mood' => $filters['mood'] ?? null, 'q' => $filters['q'] ?? null])) }}"><i>edit</i> Edit</a>
                                <form method="POST" action="{{ route('journals.toggle', [$selectedEntry, 'favorite']) }}">@csrf @method('PATCH')<button class="entry-icon {{ $selectedEntry->is_favorite ? 'active' : '' }}" type="submit" aria-label="Toggle favorite">@include('partials.icons.heart')</button></form>
                                <form method="POST" action="{{ route('journals.toggle', [$selectedEntry, 'bookmark']) }}">@csrf @method('PATCH')<button class="entry-icon {{ $selectedEntry->is_bookmarked ? 'active' : '' }}" type="submit" aria-label="Toggle bookmark">@include('partials.icons.bookmark')</button></form>
                                <form method="POST" action="{{ route('journals.destroy', $selectedEntry) }}" data-confirm="Remove this reflection permanently?">@csrf @method('DELETE')<button class="journal-delete-button" type="submit"><i>delete</i> Delete</button></form>
                            </div>
                        </footer>
                    </article>
                @endif
            </section>
            @endif
        </div>
    </div>
@endsection

@if ($archiveCount > 0 && ! $editingEntry)
    @section('modals')
        <div class="modal {{ $errors->any() ? 'open' : '' }}" id="journalModal" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
            <button class="modal-backdrop" type="button" data-close-modal aria-label="Close new journal form"></button>
            <form class="modal-card journal-modal-card journal-editor-form" method="POST" action="{{ route('journals.store') }}" enctype="multipart/form-data" role="dialog" aria-modal="true" aria-labelledby="journalModalTitle">
                @csrf
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i>close</i></button>
                <span class="modal-icon"><i>edit_note</i></span>
                <small>Create a mindful note</small>
                <h2 id="journalModalTitle">New Entry</h2>
                @include('journals.partials.editor-fields', [
                    'editingEntry' => null,
                    'formDate' => old('entry_date', $today->toDateString()),
                    'formMood' => old('mood'),
                    'formTags' => old('tags', ''),
                ])
            </form>
        </div>
    @endsection
@endif
