<?php

namespace App\Http\Requests;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $timezone = $user instanceof User ? $user->timezone : config('app.timezone');
        $today = CarbonImmutable::now($timezone)->toDateString();

        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['health', 'productivity', 'mindfulness'])],
            'start_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
