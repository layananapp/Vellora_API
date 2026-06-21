<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'code',
        'voucher_name',
        'discount_type',
        'discount_value',
        'minimum_transaction',
        'quota',
        'used',
        'expired_at',
        'is_active'
    ];
}
