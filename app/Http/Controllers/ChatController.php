<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\TimKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function users(): JsonResponse
    {
        $auth = auth()->user();
        $authId = $auth->id;

        $unreadCounts = ChatMessage::selectRaw('sender_id, COUNT(*) as total')
            ->where('receiver_id', $authId)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        $privateMessages = ChatMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->whereNotNull('receiver_id');
        })->orWhere('receiver_id', $authId)
            ->orderByDesc('id')
            ->get();

        $lastByPartner = [];
        foreach ($privateMessages as $pm) {
            $partnerId = $pm->sender_id === $authId ? $pm->receiver_id : $pm->sender_id;
            if (!isset($lastByPartner[$partnerId])) {
                $lastByPartner[$partnerId] = $pm;
            }
        }

        $generalLast = ChatMessage::with('sender:id,name')
            ->whereNull('receiver_id')
            ->whereNull('tim_kerja_id')
            ->orderByDesc('id')
            ->first();

        // Daftar Grup Tim Kerja
        $teamsQuery = TimKerja::orderBy('nama');
        if (!$auth->hasAnyRole(['admin', 'pimpinan', 'keuangan']) && $auth->tim_kerja_id) {
            $teamsQuery->where('id', $auth->tim_kerja_id);
        }
        $teamModels = $teamsQuery->get();

        $teamMessages = ChatMessage::with('sender:id,name')
            ->whereNotNull('tim_kerja_id')
            ->whereNull('receiver_id')
            ->whereIn('tim_kerja_id', $teamModels->pluck('id'))
            ->orderByDesc('id')
            ->get();

        $lastByTeam = [];
        foreach ($teamMessages as $tm) {
            if (!isset($lastByTeam[$tm->tim_kerja_id])) {
                $lastByTeam[$tm->tim_kerja_id] = $tm;
            }
        }

        $teams = $teamModels->map(function (TimKerja $t) use ($lastByTeam) {
            $last = $lastByTeam[$t->id] ?? null;
            return [
                'id' => $t->id,
                'kode' => $t->kode,
                'nama' => $t->nama,
                'last_message' => $last ? (($last->sender?->name ?? 'User') . ': ' . $last->message) : "Grup diskusi {$t->nama}",
                'last_time' => $last ? $this->formatChatTime($last->created_at) : '',
                'is_today' => $last ? $last->created_at->isToday() : false,
                'last_ts' => $last ? $last->created_at->timestamp : 0,
            ];
        })->sortByDesc('last_ts')->values();

        $users = User::where('id', '!=', $authId)
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($unreadCounts, $lastByPartner, $authId) {
                $last = $lastByPartner[$u->id] ?? null;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role_label' => $u->roleLabel(),
                    'unread' => (int) ($unreadCounts[$u->id] ?? 0),
                    'last_message' => $last ? (($last->sender_id === $authId ? 'Anda: ' : '') . $last->message) : 'Belum ada percakapan',
                    'last_time' => $last ? $this->formatChatTime($last->created_at) : '',
                    'is_today' => $last ? $last->created_at->isToday() : false,
                    'last_ts' => $last ? $last->created_at->timestamp : 0,
                ];
            })
            ->sortByDesc('last_ts')
            ->values();

        return response()->json([
            'general' => [
                'last_message' => $generalLast ? (($generalLast->sender?->name ?? 'User') . ': ' . $generalLast->message) : 'Grup diskusi seluruh tim & mitra',
                'last_time' => $generalLast ? $this->formatChatTime($generalLast->created_at) : '',
                'is_today' => $generalLast ? $generalLast->created_at->isToday() : false,
            ],
            'teams' => $teams,
            'users' => $users,
            'total_unread' => (int) $unreadCounts->sum(),
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $authId = auth()->id();
        $receiverId = $request->filled('receiver_id') ? (int) $request->receiver_id : null;
        $timKerjaId = $request->filled('tim_kerja_id') ? (int) $request->tim_kerja_id : null;

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
        } elseif ($timKerjaId) {
            $messages = ChatMessage::with('sender:id,name,role')
                ->where('tim_kerja_id', $timKerjaId)
                ->whereNull('receiver_id')
                ->orderBy('id')
                ->take(80)
                ->get();
        } else {
            $messages = ChatMessage::with('sender:id,name,role')
                ->whereNull('receiver_id')
                ->whereNull('tim_kerja_id')
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
            'tim_kerja_id' => ['nullable', 'integer', 'exists:tim_kerjas,id'],
        ]);

        $authId = auth()->id();
        $timKerjaId = $data['tim_kerja_id'] ?? null;
        $receiverId = $timKerjaId ? null : ($data['receiver_id'] ?? null);

        if ($receiverId !== null && (int) $receiverId === $authId) {
            return response()->json(['message' => 'Tidak dapat mengirim pesan privat ke diri sendiri.'], 422);
        }

        $chat = ChatMessage::create([
            'sender_id' => $authId,
            'receiver_id' => $receiverId,
            'tim_kerja_id' => $timKerjaId,
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

    private function formatChatTime(Carbon $dt): string
    {
        if ($dt->isToday()) {
            return $dt->format('H:i');
        }
        if ($dt->isYesterday()) {
            return 'Kemarin';
        }
        return $dt->translatedFormat('D');
    }
}
