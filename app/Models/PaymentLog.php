<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;

class PaymentLog extends Model
{
    protected $fillable = [
        'payment_id',
        'status',
        'message',
        'payload'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
