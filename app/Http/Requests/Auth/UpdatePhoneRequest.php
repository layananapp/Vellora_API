<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhoneRequest extends FormRequest
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
            'phone_number' => [
                'required',
                'string',
                'regex:/^(\+62|62|0)[0-9]{8,12}$/',
                'max:15',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'Nomor HP wajib diisi.',
            'phone_number.regex'    => 'Format nomor HP tidak valid. Gunakan format 08xx, +628xx, atau 628xx.',
            'phone_number.max'      => 'Nomor HP maksimal 15 karakter.',
            'phone_number.unique'   => 'Nomor HP ini sudah digunakan oleh akun lain.',
        ];
    }
}
