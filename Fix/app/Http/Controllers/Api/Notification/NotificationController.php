<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $user = $request->get('user');
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($n) => $this->format($n));

        return response()->json(['status' => true, 'data' => $notifications]);
    }

    public function getUnreadCount(Request $request)
    {
        $user  = $request->get('user');
        $count = Notification::where('user_id', $user->id)->where('is_read', false)->count();
        return response()->json(['status' => true, 'data' => ['count' => $count]]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user  = $request->get('user');
        $notif = Notification::where('id', $id)->where('user_id', $user->id)->first();
        if (!$notif) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        $notif->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['status' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->get('user');
        Notification::where('user_id', $user->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Semua sudah dibaca']);
    }

    public function deleteAll(Request $request)
    {
        $user = $request->get('user');
        Notification::where('user_id', $user->id)->delete();
        return response()->json(['status' => true, 'message' => 'Semua notifikasi dihapus']);
    }

    private function format(Notification $n): array
    {
        return [
            'id'         => $n->id,
            'type'       => $n->type,
            'icon'       => \App\Models\Notification::TYPE_ICON[$n->type] ?? 'notifications-outline',
            'title'      => $n->title,
            'message'    => $n->message,
            'data'       => $n->data,
            'is_read'    => $n->is_read,
            'time'       => $n->created_at->diffForHumans(),
            'created_at' => $n->created_at->toIso8601String(),
        ];
    }
}