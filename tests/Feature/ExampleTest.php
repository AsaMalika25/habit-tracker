<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_dashboard_is_the_home_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('Daily Habits &amp; Rituals', false)
            ->assertDontSee('Search habits, notes...')
            ->assertDontSee('Quick Entry')
            ->assertDontSee('New Habit');
    }

    public function test_photobooth_saves_photos_separately_from_journals(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->journals()->create([
            'entry_date' => now()->toDateString(),
            'title' => 'Garden Light',
            'content' => 'A moment worth keeping.',
            'mood' => 'happy',
            'mood_score' => 5,
            'image_url' => 'https://example.com/garden.jpg',
        ]);

        $this->actingAs($user)
            ->post(route('memories.photos.store'), [
                'photo' => UploadedFile::fake()->image('capture.jpg', 640, 480),
                'filter' => 'collage',
            ])
            ->assertRedirectToRoute('memories.photobooth');

        $photo = $user->photos()->sole();
        $this->assertSame('collage', $photo->filter);
        Storage::disk('public')->assertExists($photo->path);

        $this->actingAs($user)
            ->get(route('memories.photobooth'))
            ->assertOk()
            ->assertViewIs('memories.photobooth')
            ->assertSee('Photobooth Sanctuary')
            ->assertSee('Collage memory')
            ->assertSee('data-photo-filter="collage"', false)
            ->assertSee('data-collage-progress', false)
            ->assertSee(asset('storage/'.$photo->path), false)
            ->assertDontSee('https://example.com/garden.jpg', false)
            ->assertSee('data-open-photobooth', false)
            ->assertSee('Photobooth');
    }

    public function test_timeline_groups_past_habits_and_journals_without_leaking_data(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $other = User::factory()->create();
        $pastDate = now($user->timezone)->subDay()->toDateString();
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Evening walk',
            'category' => 'health',
            'start_date' => $pastDate,
        ]);
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => $pastDate,
            'completed_at' => now($user->timezone)->subDay(),
        ]);
        Journal::create([
            'user_id' => $user->id,
            'entry_date' => $pastDate,
            'title' => 'A steady evening',
            'content' => 'I made space for a quiet walk.',
            'mood_score' => 5,
            'mood' => 'happy',
        ]);
        Journal::create([
            'user_id' => $user->id,
            'entry_date' => now($user->timezone)->toDateString(),
            'title' => 'Still today',
            'content' => 'This belongs on the timeline tomorrow.',
            'mood_score' => 5,
            'mood' => 'happy',
        ]);
        Journal::create([
            'user_id' => $other->id,
            'entry_date' => $pastDate,
            'title' => 'Someone else’s reflection',
            'content' => 'Private.',
            'mood_score' => 2,
            'mood' => 'sad',
        ]);

        $this->actingAs($user)
            ->get(route('memories.timeline'))
            ->assertOk()
            ->assertViewIs('memories.timeline')
            ->assertViewHas('timeline', fn ($timeline) => $timeline->count() === 1)
            ->assertSee('Evening walk')
            ->assertSee('A steady evening')
            ->assertDontSee('Still today')
            ->assertDontSee('Someone else’s reflection')
            ->assertSee('Timeline');
    }
}
