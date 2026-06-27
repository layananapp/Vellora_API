<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Semua user yang sudah terautentikasi (via JWT middleware) boleh request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'different:old_password',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.different' => 'Password baru tidak boleh sama dengan password lama.',
            'new_password.min'       => 'Password baru minimal 8 karakter.',
        ];
    }
}
