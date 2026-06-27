<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'chat.{roomId}',
    function ($user, $roomId) {

        $room = \App\Models\ChatRoom::find($roomId);

        return $room && (
            $room->buyer_id  == $user->id ||
            $room->seller_id == $user->id
        );

    }
);