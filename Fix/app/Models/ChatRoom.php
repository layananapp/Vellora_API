<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product;
use App\Models\Chat;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_id'
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id')->withTrashed();
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Chat::class)
            ->latestOfMany();
    }
}
