<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used'    => 'boolean',
    ];

    /*
    |--------------------------------------------------
    | Cek apakah OTP masih valid
    |--------------------------------------------------
    */
    public function isValid(): bool
    {
        return ! $this->is_used
            && now()->lessThan($this->expires_at);
    }

    /*
    |--------------------------------------------------
    | Scope: OTP aktif untuk email tertentu
    |--------------------------------------------------
    */
    public function scopeActiveFor($query, string $email)
    {
        return $query
            ->where('email', $email)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest();
    }
}