<?php

namespace App\Http\Requests;

use App\Models\Journal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $journal = $this->route('journal');

        return ! $journal instanceof Journal || $journal->user_id === $this->user()?->id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        abort_unless($user instanceof User, 401);
        $today = CarbonImmutable::now($user->timezone)->toDateString();

        return [
            'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:30000'],
            'mood' => ['required', Rule::in(['happy', 'sad', 'worried'])],
            'tags' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'audio_url' => ['nullable', 'url:http,https', 'max:2048'],
            'action' => ['nullable', Rule::in(['save', 'publish'])],
        ];
    }
}
