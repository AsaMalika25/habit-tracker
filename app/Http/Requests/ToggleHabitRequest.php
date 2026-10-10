<?php

namespace App\Http\Requests;

use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $user = $this->user();
        $timezone = $user instanceof User ? $user->timezone : config('app.timezone');
        $today = CarbonImmutable::now($timezone)->toDateString();
        $habit = $this->route('habit');
        $habitDate = $habit instanceof Habit
            ? ($habit->start_date ?? $habit->created_at)->toDateString()
            : $today;

        return [
            'log_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today, Rule::in([$habitDate])],
        ];
    }
}
