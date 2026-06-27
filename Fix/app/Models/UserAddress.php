<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone_number',
        'full_address',
        'detail_address',
        'postal_code',
        'is_default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}