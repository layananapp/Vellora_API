<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title',
        'message', 'data', 'is_read', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    const TYPE_ICON = [
        'order_placed'     => 'bag-handle-outline',
        'payment_reminder' => 'time-outline',
        'payment_verified' => 'checkmark-circle-outline',
        'order_processing' => 'cube-outline',
        'order_shipped'    => 'car-outline',
        'order_delivered'  => 'star-outline',
        'order_cancelled'  => 'close-circle-outline',
        'general'          => 'notifications-outline',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}