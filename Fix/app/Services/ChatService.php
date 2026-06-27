<?php

namespace App\Services;

use App\Models\ChatRoom;
use App\Models\Chat;
use App\Events\MessageSent;
use Illuminate\Http\UploadedFile;

class ChatService
{
    private function getStoragePath(string $subFolder = ''): string
    {
        $root = base_path('/../storage');
        return $subFolder ? $root . '/' . trim($subFolder, '/') : $root;
    }

    public function createRoom($user, $validated)
    {
        if ($user->id == $validated['seller_id']) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak bisa membuat chat dengan diri sendiri'
            ], 422);
        }

        $room = ChatRoom::where('buyer_id', $user->id)
            ->where('seller_id', $validated['seller_id'])
            ->first();

        if (!$room) {
            $room = ChatRoom::create([
                'buyer_id'   => $user->id,
                'seller_id'  => $validated['seller_id'],
                'product_id' => $validated['product_id'] ?? null
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Chat room berhasil dibuat',
            'data'    => $room
        ]);
    }

    public function sendMessage(
        $user,
        $roomId,
        $validated,
        ?UploadedFile $imageFile = null,
        ?string $uploadId = null
    ) {
        $room = ChatRoom::find($roomId);

        if (!$room) {
            return response()->json([
                'status'  => false,
                'message' => 'Chat room tidak ditemukan'
            ], 404);
        }

        if (
            $room->buyer_id  != $user->id &&
            $room->seller_id != $user->id
        ) {
            return response()->json([
                'status'  => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $imagePath = null;

        if ($imageFile) {
            $filename   = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
            $destFolder = $this->getStoragePath('chat-images');

            if (!is_dir($destFolder)) {
                mkdir($destFolder, 0775, true);
            }

            $imageFile->move($destFolder, $filename);
            $imagePath = 'chat-images/' . $filename;
        }

        $chat = Chat::create([
            'chat_room_id' => $room->id,
            'sender_id'    => $user->id,
            'message'      => $validated['message'] ?? null,
            'image_path'   => $imagePath,
        ]);

        $chat->load('sender');

        // Kirim notifikasi ke penerima chat
        try {
            $recipientId = ($room->buyer_id == $user->id) ? $room->seller_id : $room->buyer_id;
            \App\Services\NotificationService::create(
                $recipientId,
                'chat',
                'Pesan Baru 💬',
                "{$user->name}: " . ($chat->message ?? 'Mengirim gambar'),
                ['chat_room_id' => $room->id]
            );
        } catch (\Exception $e) {
            // Abaikan
        }

        $chatArray              = $chat->toArray();
        $chatArray['upload_id'] = $uploadId;

        broadcast(new MessageSent($chatArray))->toOthers();

        return response()->json([
            'status'  => true,
            'message' => 'Pesan berhasil dikirim',
            'data'    => $chat   
        ]);
    }
}