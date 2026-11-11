<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Journal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 08:45:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_create_publish_update_and_delete_a_reflection(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

        $this->actingAs($user)
            ->get(route('journals.index'))
            ->assertOk()
            ->assertSee('Mindful Journal &amp; Reflections', false)
            ->assertSee('Journal Sanctuary')
            ->assertDontSee('Quick Entry')
            ->assertDontSee('New Entry')
            ->assertDontSee('Begin writing')
            ->assertDontSee('>Find<', false)
            ->assertDontSee('name="date"', false)
            ->assertSee('book-symbol', false)
            ->assertSee('😊 Senang', false)
            ->assertSee('calendar-symbol', false)
            ->assertSee('timer-symbol', false)
            ->assertSee('bulb-symbol', false)
            ->assertSee('attachment-symbol', false)
            ->assertSee('list-symbol', false)
            ->assertSee('quote-symbol', false)
            ->assertSee('link-symbol', false)
            ->assertSee('heart-symbol', false)
            ->assertSee('bookmark-symbol', false)
            ->assertSee('camera-symbol', false)
            ->assertSee('name="image"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertDontSee('name="mood" type="radio" value="happy" checked', false)
            ->assertDontSee('name="mood" type="radio" value="sad" checked', false)
            ->assertDontSee('name="mood" type="radio" value="worried" checked', false);

        $draftResponse = $this->actingAs($user)
            ->post(route('journals.store'), [
                'entry_date' => '2026-09-21',
                'title' => 'Morning Stillness',
                'content' => 'A quiet and deliberate beginning to the day.',
                'mood' => 'happy',
                'tags' => 'gratitude, mindfulness, gratitude',
                'image_url' => 'https://example.com/morning.jpg',
                'action' => 'save',
            ]);

        $journal = Journal::query()->whereBelongsTo($user)->sole();
        $draftResponse->assertRedirectToRoute('memories.drafts', ['entry' => $journal]);
        $this->assertIsInt($journal->id);
        $this->assertSame('Morning Stillness', $journal->title);
        $this->assertSame(['gratitude', 'mindfulness'], $journal->tags);
        $this->assertSame(5, $journal->mood_score);
        $this->assertNull($journal->published_at);

        $this->actingAs($user)
            ->get(route('memories.drafts', ['entry' => $journal]))
            ->assertOk()
            ->assertSee('Journal Drafts &amp; Reflections', false)
            ->assertSee('Morning Stillness')
            ->assertSee('Private draft')
            ->assertSee('aria-label="Draft viewer"', false)
            ->assertDontSee('data-open-modal="#journalModal"', false)
            ->assertDontSee(route('journals.toggle', [$journal, 'favorite']))
            ->assertDontSee(route('journals.destroy', $journal))
            ->assertDontSee('journal-edit-button', false);

        $this->actingAs($user)
            ->get(route('journals.index'))
            ->assertOk()
            ->assertSee('Morning Stillness')
            ->assertSee('aria-label="Delete Morning Stillness"', false)
            ->assertDontSee('has-selected', false)
            ->assertDontSee('journal-detail', false);

        $this->actingAs($user)
            ->get(route('journals.index', ['entry' => $journal->id]))
            ->assertOk()
            ->assertSee('New Entry')
            ->assertSee('has-entries', false)
            ->assertSee('has-selected', false)
            ->assertSee('journal-detail', false)
            ->assertSee('archive-entry-link" href="'.route('journals.index', ['scope' => 'all']), false)
            ->assertSee('data-open-modal="#journalModal"', false)
            ->assertSee('journalModal', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('journal-formatbar', false)
            ->assertSee('name="image"', false)
            ->assertSee('Reflection tags')
            ->assertSee('Save Draft')
            ->assertSee('Publish to Archive')
            ->assertDontSee('name="mood" type="radio" value="happy" checked', false)
            ->assertSee(route('journals.index', ['entry' => $journal->id, 'edit' => 1, 'scope' => 'all']))
            ->assertSee('Private draft');

        $this->actingAs($user)
            ->get(route('journals.index', ['entry' => $journal->id, 'edit' => 1]))
            ->assertOk()
            ->assertSee('id="journalEditor"', false)
            ->assertSee('action="'.route('journals.update', $journal).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Morning Stillness"', false)
            ->assertSee('A quiet and deliberate beginning to the day.')
            ->assertSee('gratitude, mindfulness')
            ->assertSee('Save Changes')
            ->assertSee('Cancel')
            ->assertDontSee('journal-detail', false)
            ->assertDontSee('journalModal', false);

        $this->actingAs($user)
            ->put(route('journals.update', $journal), [
                'entry_date' => '2026-09-21',
                'title' => 'Morning Stillness & Clarity',
                'content' => 'The edited reflection remains calm and intentional.',
                'mood' => 'worried',
                'tags' => 'focus',
                'action' => 'publish',
            ])
            ->assertRedirectToRoute('journals.index', ['entry' => $journal]);

        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'title' => 'Morning Stillness & Clarity',
            'mood' => 'worried',
            'mood_score' => 2,
        ]);
        $this->assertNotNull($journal->fresh()->published_at);

        $this->actingAs($user)
            ->patch(route('journals.toggle', [$journal, 'favorite']))
            ->assertRedirect();
        $this->assertTrue($journal->fresh()->is_favorite);

        $this->actingAs($user)
            ->delete(route('journals.destroy', $journal))
            ->assertRedirectToRoute('journals.index');
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
    }

    public function test_user_can_upload_and_delete_a_private_journal_image(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

        $this->actingAs($user)
            ->post(route('journals.store'), [
                'entry_date' => '2026-09-21',
                'title' => 'Local Memory',
                'content' => 'A photo selected from this computer.',
                'mood' => 'happy',
                'image' => UploadedFile::fake()->image('memory.jpg'),
            ])
            ->assertRedirect();

        $journal = Journal::query()->whereBelongsTo($user)->sole();
        $path = $journal->image_url;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('journals/', $path);
        Storage::disk('s3')->assertExists($path);

        $this->actingAs($user)
            ->get(route('memories.drafts', ['entry' => $journal]))
            ->assertOk()
            ->assertSee('/'.$path.'?expiration=', false);

        $this->actingAs($user)
            ->put(route('journals.update', $journal), [
                'entry_date' => '2026-09-21',
                'title' => 'Updated Local Memory',
                'content' => 'The local photo was replaced.',
                'mood' => 'happy',
                'image' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertRedirectToRoute('memories.drafts', ['entry' => $journal]);

        Storage::disk('s3')->assertMissing($path);
        $replacementPath = $journal->fresh()->image_url;
        $this->assertNotNull($replacementPath);
        Storage::disk('s3')->assertExists($replacementPath);

        $this->actingAs($user)
            ->delete(route('journals.destroy', $journal))
            ->assertRedirectToRoute('journals.index');

        Storage::disk('s3')->assertMissing($replacementPath);
    }

    public function test_draft_archive_only_shows_the_users_unpublished_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $draft = $user->journals()->create([
            'entry_date' => '2026-09-21',
            'title' => 'Unfinished thought',
            'content' => 'Only the owner should see this draft.',
            'mood' => 'happy',
            'mood_score' => 5,
        ]);
        $published = $user->journals()->create([
            'entry_date' => '2026-09-20',
            'title' => 'Published thought',
            'content' => 'This belongs in the journal archive.',
            'mood' => 'happy',
            'mood_score' => 5,
            'published_at' => now(),
        ]);
        $otherDraft = $other->journals()->create([
            'entry_date' => '2026-09-21',
            'title' => 'Another private draft',
            'content' => 'This belongs to another user.',
            'mood' => 'sad',
            'mood_score' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('memories.drafts'))
            ->assertOk()
            ->assertSee('href="'.route('memories.drafts').'"', false)
            ->assertSee($draft->title)
            ->assertDontSee($published->title)
            ->assertDontSee($otherDraft->title);

        $this->actingAs($user)
            ->get(route('memories.drafts', ['entry' => $published]))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('memories.drafts', ['entry' => $otherDraft]))
            ->assertNotFound();
    }

    public function test_journal_screen_filters_entries_and_shows_completed_rituals(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'title' => 'Morning Meditation',
            'category' => 'mindfulness',
            'start_date' => '2026-09-21',
        ]);
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => '2026-09-21',
            'completed_at' => '2026-09-21 07:00:00',
        ]);
        $selected = Journal::create([
            'user_id' => $user->id,
            'entry_date' => '2026-09-21',
            'title' => 'System Clarity',
            'content' => 'Focused architecture work.',
            'mood_score' => 4,
            'mood' => 'focused',
            'tags' => ['deepwork'],
            'is_favorite' => true,
        ]);
        Journal::create([
            'user_id' => $user->id,
            'entry_date' => '2026-09-20',
            'title' => 'Slow Sunday',
            'content' => 'A grounded day.',
            'mood_score' => 4,
            'mood' => 'grounded',
        ]);

        $this->actingAs($user)
            ->get(route('journals.index'))
            ->assertOk()
            ->assertSee('System Clarity')
            ->assertSee('Slow Sunday');

        $this->actingAs($user)
            ->get(route('journals.index', ['scope' => 'week']))
            ->assertOk()
            ->assertSee('System Clarity')
            ->assertDontSee('Slow Sunday');

        $this->actingAs($user)
            ->get(route('journals.index', ['scope' => 'favorites', 'entry' => $selected->id]))
            ->assertOk()
            ->assertSee('System Clarity')
            ->assertSee('Morning Meditation')
            ->assertDontSee('Slow Sunday');

        $this->actingAs($user)
            ->get(route('journals.index', ['q' => 'Slow']))
            ->assertOk()
            ->assertSee('Slow Sunday')
            ->assertDontSee('System Clarity');
    }

    public function test_journals_can_share_a_date_and_other_users_cannot_change_an_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $journal = Journal::create([
            'user_id' => $owner->id,
            'entry_date' => '2026-09-21',
            'title' => 'Private Reflection',
            'content' => 'For the owner only.',
            'mood_score' => 5,
            'mood' => 'calm',
        ]);

        $this->actingAs($owner)
            ->post(route('journals.store'), [
                'entry_date' => '2026-09-21',
                'title' => 'Second Reflection',
                'content' => 'Another entry from the same day.',
                'mood' => 'happy',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('journals', [
            'user_id' => $owner->id,
            'title' => 'Second Reflection',
        ]);
        $this->assertSame(2, Journal::query()->whereBelongsTo($owner)->whereDate('entry_date', '2026-09-21')->count());

        $this->actingAs($other)
            ->put(route('journals.update', $journal), [
                'entry_date' => '2026-09-21',
                'title' => 'Changed',
                'content' => 'Unauthorized.',
                'mood' => 'happy',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('journals.destroy', $journal))
            ->assertNotFound();
    }
}
