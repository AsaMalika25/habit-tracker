@extends('layouts.app')

@section('title', 'Journal Sanctuary — Aura')

@section('content')
    @php
        $activeScope = $filters['scope'] ?? 'all';
        $formDate = old('entry_date', $today->toDateString());
        $formMood = old('mood', 'happy');
        $formTags = old('tags', '');
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
                @if ($archiveCount > 0)
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
                @if ($archiveCount === 0)
                <form id="journalEditor" method="POST" action="{{ route('journals.store') }}">
                    @csrf

                    <div class="editor-meta-bar">
                        <label>@include('partials.icons.calendar')<input name="entry_date" type="date" value="{{ $formDate }}" max="{{ $today->toDateString() }}" required></label>
                        <span><i>schedule</i>{{ $today->format('h:i A') }}</span>
                        <span class="meta-spacer"></span>
                        <span><i>cloud</i>Private draft</span>
                        <span>@include('partials.icons.timer')<b id="journalReadTime">1 min read</b></span>
                    </div>

                    <div class="guided-reflection">
                        <span class="guided-icon">@include('partials.icons.bulb')</span>
                        <div><small>Today's Guided Reflection</small><p>“{{ $prompt }}”</p></div>
                        <button type="button" title="A new prompt is shown each day">@include('partials.icons.bulb')</button>
                    </div>

                    <input class="journal-title-input" name="title" type="text" value="{{ old('title') }}" maxlength="160" placeholder="Title your reflection..." required autofocus>
                    <span class="title-underline"></span>

                    <fieldset class="resonance-picker">
                        <legend>Current Resonance</legend>
                        <div>
                            @foreach ($moods as $key => $mood)
                                <label class="{{ $formMood === $key ? 'active' : '' }}"><input name="mood" type="radio" value="{{ $key }}" @checked($formMood === $key) required><span>@if (($mood['icon'] ?? null) === 'ground')@include('partials.icons.ground')@else{{ $mood['emoji'] }}@endif {{ $mood['label'] }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="journal-formatbar" aria-label="Formatting tools">
                        <div>
                            <button type="button" data-journal-format="bold" title="Bold"><strong class="format-letter">B</strong></button>
                            <button type="button" data-journal-format="italic" title="Italic"><em class="format-letter">I</em></button>
                            <button type="button" data-journal-format="list" title="List">@include('partials.icons.list')</button>
                            <button type="button" data-journal-format="quote" title="Quote">@include('partials.icons.quote')</button>
                            <button type="button" data-journal-format="link" title="Link">@include('partials.icons.link')</button>
                        </div>
                        <details class="media-details">
                            <summary>@include('partials.icons.attachment') Add media</summary>
                            <div>
                                <label>@include('partials.icons.image')<input name="image_url" type="url" value="{{ old('image_url') }}" placeholder="https://... image URL"></label>
                                <label>@include('partials.icons.microphone')<input name="audio_url" type="url" value="{{ old('audio_url') }}" placeholder="https://... audio URL"></label>
                            </div>
                        </details>
                    </div>

                    <textarea id="journalContent" class="journal-content-input" name="content" rows="13" maxlength="30000" placeholder="Begin with what feels present right now..." required>{{ old('content') }}</textarea>

                    <label class="journal-tags-input"><span><i>tag</i> Reflection tags</span><input name="tags" type="text" value="{{ $formTags }}" maxlength="255" placeholder="gratitude, mindfulness, health"></label>

                    <footer class="journal-editor-footer">
                        <div><span><i>check</i>Ready to save</span><span class="footer-dot"></span><span id="journalCount">0 words · 0 characters</span></div>
                        <div>
                            <button class="secondary-button" name="action" value="save" type="submit"><i>save</i> Save Draft</button>
                            <button class="primary-button" name="action" value="publish" type="submit"><i>archive</i> Publish to Archive</button>
                        </div>
                    </footer>
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

@if ($archiveCount > 0)
    @section('modals')
        <div class="modal {{ $errors->any() ? 'open' : '' }}" id="journalModal" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
            <button class="modal-backdrop" type="button" data-close-modal aria-label="Close dialog"></button>
            <form class="modal-card journal-modal-card" method="POST" action="{{ route('journals.store') }}" role="dialog" aria-modal="true" aria-labelledby="journalModalTitle">
                @csrf
                <input name="action" type="hidden" value="save">
                <button class="modal-close" type="button" data-close-modal aria-label="Close"><i>close</i></button>
                <span class="modal-icon"><i>edit_note</i></span><small>Create a mindful note</small><h2 id="journalModalTitle">New Entry</h2>
                <label>Date<input name="entry_date" type="date" value="{{ old('entry_date', $today->toDateString()) }}" max="{{ $today->toDateString() }}" required></label>
                <label>Title<input name="title" type="text" value="{{ old('title') }}" maxlength="160" placeholder="Title your reflection" required></label>
                <fieldset class="modal-moods">
                    <legend>Mood</legend>
                    <div>
                        @foreach ($moods as $key => $mood)
                            <label><input name="mood" type="radio" value="{{ $key }}" @checked(old('mood', 'happy') === $key) required><span>{{ $mood['emoji'] }} {{ $mood['label'] }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <label>Reflection<textarea name="content" rows="7" maxlength="30000" placeholder="What feels present right now?" required>{{ old('content') }}</textarea></label>
                <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>Cancel</button><button class="primary-button" type="submit"><i>save</i> Save Draft</button></div>
            </form>
        </div>
    @endsection
@endif
