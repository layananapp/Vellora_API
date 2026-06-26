<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Chat extends Model
{
    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'message',
        'image_path',
        'is_read',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return url('storage/' . $this->image_path);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }
}