<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'venue_id'    => ['sometimes', 'required', 'integer', 'exists:venues,id'],
            'starts_at'   => ['sometimes', 'required', 'date'],
            'ends_at'     => ['sometimes', 'required', 'date'],
            'price_cents' => ['sometimes', 'integer', 'min:0', 'max:10000000'],
            'status'      => ['sometimes', Rule::enum(EventStatus::class)],
            'section_prices'   => ['nullable', 'array'],
'section_prices.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                    return;
                }

                // Resolve Event model safely regardless of route param binding
                $event = $this->route('event') ?? $this->route('id');
                if (! $event instanceof Event) {
                    $event = Event::find($event);
                }

                if (! $event) {
                    return;
                }

                $startInput = $this->input('starts_at');
                $start = $startInput ? Carbon::parse($startInput) : Carbon::parse($event->starts_at);

                $endInput = $this->input('ends_at');
                $end = $endInput ? Carbon::parse($endInput) : Carbon::parse($event->ends_at);

                if ($start && $end && $end->lte($start)) {
                    $validator->errors()->add('ends_at', 'The end date must be after the start date.');
                }
            },
        ];
    }
}