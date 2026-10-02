<?php

namespace App\Http\Requests;

use App\Services\RefundRequestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is checked in the controller
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(array_keys(RefundRequestService::REASONS))],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'accept' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Please explain your reason in at least 10 characters.',
            'accept.accepted' => 'You must confirm that you understand the refund terms.',
        ];
    }
}