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

        // Mark incoming messages as read
        Chat::where('chat_room_id', $room->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

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
            ->get()
            ->sortByDesc(fn($room) => $room->lastMessage ? $room->lastMessage->created_at : $room->created_at)
            ->values();

        // Tambahkan indikator status online di data response
        $mappedRooms = $rooms->map(function ($room) use ($user) {
            $roomArray = $room->toArray();
            
            if ($room->buyer) {
                $roomArray['buyer']['is_online'] = \Illuminate\Support\Facades\Cache::has('user-online-' . $room->buyer_id);
            }
            
            if ($room->seller) {
                $roomArray['seller']['is_online'] = \Illuminate\Support\Facades\Cache::has('user-online-' . $room->seller_id);
            }

            // Count unread messages in this room sent by the other user
            $roomArray['unread_count'] = Chat::where('chat_room_id', $room->id)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
            
            return $roomArray;
        });

        return response()->json([
            'status' => true,
            'data'   => $mappedRooms
        ]);
    }
}