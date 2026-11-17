<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function users(): JsonResponse
    {
        $authId = auth()->id();

        $unreadCounts = ChatMessage::selectRaw('sender_id, COUNT(*) as total')
            ->where('receiver_id', $authId)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        $users = User::where('id', '!=', $authId)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role_label' => $u->roleLabel(),
                'unread' => (int) ($unreadCounts[$u->id] ?? 0),
            ]);

        return response()->json([
            'users' => $users,
            'total_unread' => (int) $unreadCounts->sum(),
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $authId = auth()->id();
        $receiverId = $request->filled('receiver_id') ? (int) $request->receiver_id : null;

        if ($receiverId) {
            ChatMessage::where('sender_id', $receiverId)
                ->where('receiver_id', $authId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = ChatMessage::with('sender:id,name,role')
                ->where(function ($q) use ($authId, $receiverId) {
                    $q->where(function ($sub) use ($authId, $receiverId) {
                        $sub->where('sender_id', $authId)->where('receiver_id', $receiverId);
                    })->orWhere(function ($sub) use ($authId, $receiverId) {
                        $sub->where('sender_id', $receiverId)->where('receiver_id', $authId);
                    });
                })
                ->orderBy('id')
                ->take(80)
                ->get();
        } else {
            $messages = ChatMessage::with('sender:id,name,role')
                ->whereNull('receiver_id')
                ->orderBy('id')
                ->take(80)
                ->get();
        }

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender?->name ?? 'User',
                'sender_role' => $m->sender?->roleLabel() ?? '',
                'is_mine' => $m->sender_id === $authId,
                'message' => $m->message,
                'time' => $m->created_at->format('H:i'),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'receiver_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $authId = auth()->id();
        $receiverId = $data['receiver_id'] ?? null;

        if ($receiverId !== null && (int) $receiverId === $authId) {
            return response()->json(['message' => 'Tidak dapat mengirim pesan privat ke diri sendiri.'], 422);
        }

        $chat = ChatMessage::create([
            'sender_id' => $authId,
            'receiver_id' => $receiverId,
            'message' => trim($data['message']),
        ]);

        $chat->load('sender:id,name,role');

        return response()->json([
            'message' => [
                'id' => $chat->id,
                'sender_id' => $chat->sender_id,
                'sender_name' => $chat->sender?->name ?? 'User',
                'sender_role' => $chat->sender?->roleLabel() ?? '',
                'is_mine' => true,
                'message' => $chat->message,
                'time' => $chat->created_at->format('H:i'),
            ],
        ]);
    }
}
