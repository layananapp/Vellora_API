<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        // User diambil dari $request->get('user') yang di-set oleh JwtMiddleware
        $userId = $this->get('user')?->id;

        return [
            'name'  => ['required', 'string', 'min:2', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama wajib diisi.',
            'name.min'       => 'Nama minimal 2 karakter.',
            'name.max'       => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email ini sudah digunakan oleh akun lain.',
        ];
    }
}
