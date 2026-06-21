<?php

namespace App\Http\Controllers\Api\Chat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Models\ChatRoom;
use App\Models\Chat;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function createRoom(Request $request)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'seller_id'  => ['required', 'exists:users,id'],
            'product_id' => ['nullable', 'exists:products,id']
        ]);

        return $this->chatService->createRoom($user, $validated);
    }

    public function sendMessage(Request $request, $roomId)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'image'   => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        // Minimal salah satu harus ada
        if (empty($validated['message']) && !$request->hasFile('image')) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesan atau gambar harus diisi'
            ], 422);
        }

        return $this->chatService->sendMessage(
            $user,
            $roomId,
            $validated,
            $request->file('image'),        
            $request->input('upload_id')
        );
    }

    public function getMessages(Request $request, $roomId)
    {
        $user = $request->get('user');

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

        $messages = Chat::where('chat_room_id', $room->id)
            ->with('sender')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $messages
        ]);
    }

    public function getRooms(Request $request)
    {
        $user = $request->get('user');

        $rooms = ChatRoom::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with([
                'buyer',
                'seller.store',
                'product',
                'lastMessage'
            ])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $rooms
        ]);
    }
}