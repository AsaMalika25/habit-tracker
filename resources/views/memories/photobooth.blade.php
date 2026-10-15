@extends('layouts.app')

@section('title', 'Photobooth Sanctuary — Aura')

@section('content')
    @php($featuredMemory = $memories->first())

    <div class="memory-sanctuary">
        @if (session('status'))
            <div class="flash-message sanctuary-flash" role="status"><i>check_circle</i>{{ session('status') }}</div>
        @endif

        <section class="memory-hero">
            <div class="memory-hero-copy">
                <p class="memory-kicker"><i>auto_awesome</i> Memory Sanctuary</p>
                <h1>Photobooth Sanctuary</h1>
                <p>Keep the quiet, joyful, and ordinary moments that make your days feel like your own.</p>
                <div class="memory-actions">
                    <button class="primary-button" type="button" data-open-photobooth><i>add_a_photo</i> Add photo</button>
                    <a class="memory-secondary-action" href="{{ route('memories.keepsakes') }}"><i>collections</i> View keepsakes</a>
                </div>
                <div class="memory-summary">
                    <span><strong>{{ $memoryCount }}</strong> memories held</span>
                    <span><i>lock</i> Private to you</span>
                </div>
            </div>

            <div class="memory-booth" aria-label="Featured memory">
                <span class="memory-glow"></span>
                <article class="memory-polaroid memory-polaroid-back" aria-hidden="true"></article>
                <article class="memory-polaroid">
                    <div class="memory-photo">
                        <img src="{{ $featuredMemory ? asset('storage/'.$featuredMemory->path) : asset('images/journal-garden.png') }}" alt="Featured photobooth memory">
                        <span><i>favorite</i></span>
                    </div>
                    <div class="memory-polaroid-caption">
                        <div>
                            <strong>{{ $featuredMemory ? ucfirst($featuredMemory->filter).' moment' : 'A quiet moment' }}</strong>
                            <small>{{ $featuredMemory?->captured_at?->setTimezone($user->timezone)->format('F j, Y') ?? now($user->timezone)->format('F j, Y') }}</small>
                        </div>
                        <i>photo_camera</i>
                    </div>
                </article>
            </div>
        </section>

        <section class="memory-gallery-section" id="keepsakes">
            <header class="memory-section-heading">
                <div>
                    <p>Collected with care</p>
                    <h2>Recent keepsakes</h2>
                </div>
                <span>{{ $memories->count() }} shown</span>
            </header>

            <div class="memory-gallery">
                @forelse ($memories as $memory)
                    <article class="memory-card">
                        <img src="{{ asset('storage/'.$memory->path) }}" alt="Photobooth memory captured {{ $memory->captured_at->setTimezone($user->timezone)->format('F j, Y') }}">
                        <div class="memory-card-overlay">
                            <span>{{ $memory->captured_at->setTimezone($user->timezone)->format('M j, Y · h:i A') }}</span>
                            <h3>{{ ucfirst($memory->filter) }} memory</h3>
                        </div>
                    </article>
                @empty
                    <div class="memory-empty">
                        <span><i>add_a_photo</i></span>
                        <h2>Your first keepsake belongs here</h2>
                        <p>Open the private photobooth, choose a filter, and capture a moment.</p>
                        <button class="primary-button" type="button" data-open-photobooth>Open photobooth</button>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="memory-promises" aria-label="How memory sanctuary works">
            <article><i>lock</i><div><strong>Private by default</strong><span>Your moments remain part of your personal sanctuary.</span></div></article>
            <article><i>photo_camera</i><div><strong>Made in Photobooth</strong><span>Photos are stored separately from your journal reflections.</span></div></article>
            <article><i>auto_fix_high</i><div><strong>Choose your mood</strong><span>Preview a filter live and save it into the final photo.</span></div></article>
        </section>
    </div>

    <dialog class="photobooth-dialog" data-photobooth-dialog aria-labelledby="photoboothTitle">
        <form class="photobooth-panel" method="POST" action="{{ route('memories.photos.store') }}" enctype="multipart/form-data" data-photo-form>
            @csrf
            <input name="filter" type="hidden" value="original" data-photo-filter-value>

            <header class="photobooth-header">
                <div class="photobooth-window-controls" aria-hidden="true"><span></span><span></span><span></span></div>
                <div class="photobooth-title"><h2 id="photoboothTitle">Photo Booth</h2><small data-active-filter-label>Original effect</small></div>
                <button type="button" data-close-photobooth aria-label="Close camera"><i>close</i></button>
            </header>

            <div class="photobooth-viewport">
                <video data-camera-video autoplay muted playsinline></video>
                <canvas data-camera-canvas hidden></canvas>
                <div class="photobooth-background-preview" data-background-preview aria-hidden="true"><span>Aura Daily</span></div>
                <div class="photobooth-message" data-camera-message>
                    <i>videocam</i>
                    <p>Preparing your camera…</p>
                </div>
                <strong class="photobooth-countdown" data-camera-countdown hidden>3</strong>
                <span class="photobooth-live"><i></i> Live preview</span>
                <span class="photobooth-collage-progress" data-collage-progress aria-live="polite" hidden>Frame 1 of 4</span>
            </div>

            <fieldset class="photobooth-filters">
                <legend>Choose a filter</legend>
                <div>
                    @foreach ([
                        'original' => ['Original', 'filter_none'],
                        'noir' => ['Noir', 'contrast'],
                        'sepia' => ['Sepia', 'wb_sunny'],
                        'vivid' => ['Vivid', 'palette'],
                        'glow' => ['Glow', 'flare'],
                        'collage' => ['Collage', 'grid_view'],
                    ] as $filter => [$label, $icon])
                        <button class="{{ $filter === 'original' ? 'active' : '' }}" type="button" data-photo-filter="{{ $filter }}" aria-pressed="{{ $filter === 'original' ? 'true' : 'false' }}">
                            <span class="photobooth-filter-thumb"><video class="filter-preview--{{ $filter }}" data-filter-preview autoplay muted playsinline></video><i>{{ $icon }}</i></span>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="photobooth-backgrounds">
                <legend>Choose a background</legend>
                <div>
                    @foreach ([
                        'none' => ['None', 'crop_free'],
                        'newspaper' => ['Newspaper', 'newspaper'],
                        'film' => ['Film', 'movie'],
                        'notebook' => ['Notebook', 'menu_book'],
                    ] as $background => [$label, $icon])
                        <button class="{{ $background === 'none' ? 'active' : '' }}" type="button" data-photo-background="{{ $background }}" aria-pressed="{{ $background === 'none' ? 'true' : 'false' }}">
                            <span class="background-preview background-preview--{{ $background }}"><i>{{ $icon }}</i></span>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <footer class="photobooth-controls">
                <button class="memory-secondary-action" type="button" data-close-photobooth>Cancel</button>
                <button class="memory-secondary-action" type="button" data-retake-photo hidden><i>refresh</i> Retake</button>
                <button class="photobooth-shutter" type="button" data-capture-photo disabled aria-label="Take photo"><span><i>photo_camera</i></span></button>
                <button class="memory-secondary-action" type="button" data-share-photo hidden><i>share</i><span data-share-label>Share</span></button>
                <button class="primary-button" type="button" data-save-photo hidden><i>save</i> Save memory</button>
            </footer>
        </form>
    </dialog>
@endsection
