<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\Penugasan;
use App\Models\TimKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KalenderController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isManager = !$user->hasAnyRole(['organik', 'mitra']);

        $bulan = $request->input('bulan', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        } catch (\Throwable) {
            $currentMonth = now()->startOfMonth();
            $bulan = $currentMonth->format('Y-m');
        }

        $startOfGrid = $currentMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfGrid = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Query Penugasan (Deadline Tugas)
        $tugasQuery = Penugasan::with([
            'user',
            'kecamatan',
            'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
        ])->whereBetween('deadline_tugas', [$startOfGrid->format('Y-m-d'), $endOfGrid->format('Y-m-d')]);

        // Query AlokasiTransport (Jadwal Turun Lapangan)
        $transportQuery = AlokasiTransport::with([
            'user',
            'kecamatan',
            'penugasan.subKegiatan',
            'periodeKegiatan.masterKegiatan.timKerja',
        ])->whereBetween('tgl_tugas', [$startOfGrid->format('Y-m-d'), $endOfGrid->format('Y-m-d')]);

        if (!$isManager) {
            $tugasQuery->where('user_id', $user->id);
            $transportQuery->where('user_id', $user->id);
        } else {
            if ($request->filled('user_id')) {
                $tugasQuery->where('user_id', $request->user_id);
                $transportQuery->where('user_id', $request->user_id);
            }
            if ($request->filled('tim_kerja_id')) {
                $timId = $request->tim_kerja_id;
                $tugasQuery->whereHas('subKegiatan.periodeKegiatan.masterKegiatan', fn ($q) => $q->where('tim_kerja_id', $timId));
                $transportQuery->whereHas('periodeKegiatan.masterKegiatan', fn ($q) => $q->where('tim_kerja_id', $timId));
            }
            if ($request->filled('kecamatan_id')) {
                $tugasQuery->where('kecamatan_id', $request->kecamatan_id);
                $transportQuery->where('kecamatan_id', $request->kecamatan_id);
            }
        }

        if ($request->filled('jenis_tahapan')) {
            $jenis = $request->jenis_tahapan;
            $tugasQuery->whereHas('subKegiatan', fn ($q) => $q->where('jenis_tahapan', $jenis));
            $transportQuery->whereHas('penugasan.subKegiatan', fn ($q) => $q->where('jenis_tahapan', $jenis));
        }

        if ($request->filled('status')) {
            $tugasQuery->where('status', $request->status);
            $transportQuery->whereHas('penugasan', fn ($q) => $q->where('status', $request->status));
        }

        $eventsByDate = [];

        foreach ($transportQuery->get() as $tr) {
            $dateKey = $tr->tgl_tugas->format('Y-m-d');
            $eventsByDate[$dateKey][] = [
                'type' => 'transport',
                'title' => ($isManager ? ($tr->user?->name . ' - ') : '') . ($tr->penugasan?->subKegiatan?->nama ?? 'Turun Lapangan'),
                'petugas' => $tr->user?->name ?? '-',
                'kegiatan' => $tr->periodeKegiatan?->masterKegiatan?->nama ?? '-',
                'periode' => $tr->periodeKegiatan?->nama_periode ?? '-',
                'sub_kegiatan' => $tr->penugasan?->subKegiatan?->nama ?? '-',
                'jenis' => 'Turun Lapangan (Transport)',
                'kecamatan' => $tr->kecamatan?->nama ?? '-',
                'tanggal' => $tr->tgl_tugas->format('d M Y'),
                'status' => $tr->penugasan?->status ?? 'Aktif',
                'info' => 'Transport Rp ' . number_format($tr->nominal, 0, ',', '.') . ' (' . $tr->status_spj . ')',
                'color' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            ];
        }

        foreach ($tugasQuery->get() as $t) {
            $dateKey = $t->deadline_tugas->format('Y-m-d');
            $jenis = $t->subKegiatan?->jenis_tahapan ?? 'Teknis';
            $isOverdue = $t->status !== 'Selesai' && $t->deadline_tugas->lt(today());

            if ($t->status === 'Selesai') {
                $color = 'bg-slate-100 text-slate-600 border-slate-200 line-through';
            } elseif ($isOverdue) {
                $color = 'bg-rose-50 text-rose-800 border-rose-200';
            } elseif ($jenis === 'Lapangan') {
                $color = 'bg-amber-50 text-amber-800 border-amber-200';
            } elseif ($jenis === 'Administrasi') {
                $color = 'bg-violet-50 text-violet-800 border-violet-200';
            } else {
                $color = 'bg-sky-50 text-sky-800 border-sky-200';
            }

            $eventsByDate[$dateKey][] = [
                'type' => 'deadline',
                'title' => ($isManager ? ($t->user?->name . ' - ') : '') . ($t->subKegiatan?->nama ?? 'Tugas'),
                'petugas' => $t->user?->name ?? '-',
                'kegiatan' => $t->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama ?? '-',
                'periode' => $t->subKegiatan?->periodeKegiatan?->nama_periode ?? '-',
                'sub_kegiatan' => $t->subKegiatan?->nama ?? '-',
                'jenis' => "Deadline {$jenis}",
                'kecamatan' => $t->kecamatan?->nama ?? '-',
                'tanggal' => $t->deadline_tugas->format('d M Y'),
                'status' => $t->status,
                'info' => "Target: {$t->realisasi_volume}/{$t->target_volume} {$t->satuan_volume} ({$t->progres_persen}%)",
                'color' => $color,
            ];
        }

        // Build calendar days
        $days = [];
        $cursor = $startOfGrid->copy();
        while ($cursor->lte($endOfGrid)) {
            $key = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $key,
                'day' => $cursor->day,
                'in_month' => $cursor->month === $currentMonth->month,
                'is_today' => $cursor->isToday(),
                'events' => $eventsByDate[$key] ?? [],
            ];
            $cursor->addDay();
        }

        $prevBulan = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextBulan = $currentMonth->copy()->addMonth()->format('Y-m');

        $timKerjas = $isManager ? TimKerja::orderBy('nama')->get() : collect();
        $usersList = $isManager ? User::whereIn('role', ['organik', 'mitra', 'katim'])->orderBy('name')->get() : collect();
        $kecamatans = $isManager ? Kecamatan::orderBy('nama')->get() : collect();

        return view('kalender.index', compact(
            'isManager',
            'bulan',
            'currentMonth',
            'days',
            'prevBulan',
            'nextBulan',
            'timKerjas',
            'usersList',
            'kecamatans'
        ));
    }
}
