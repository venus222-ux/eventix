<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mimes = config('eventix.banner.mimes') ?? ['jpg', 'jpeg', 'png', 'webp'];
        $maxKb = config('eventix.banner.max_kb') ?? 2048;

        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'venue_id'    => ['required', 'integer', 'exists:venues,id'],
            'starts_at'   => ['required', 'date', 'after:now'],
            'ends_at'     => ['required', 'date', 'after:starts_at'],
            'status'      => ['sometimes', Rule::enum(EventStatus::class)],
            'price_cents' => ['required', 'integer', 'min:0', 'max:10000000'],
            'section_prices'   => ['nullable', 'array'],
'section_prices.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],

            'banner'      => [
                'nullable',
                'image',
                'mimes:' . implode(',', $mimes),
                'max:' . $maxKb,
            ],
        ];
    }
}