<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'email' => 'required|email|unique:users,email,'.$userId,
            'password' => 'nullable|min:6|confirmed',
            'billing_country'     => ['nullable', 'string', 'size:2', 'alpha'],
            'billing_city'        => ['nullable', 'string', 'max:120'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_street'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
