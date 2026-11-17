<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\Penugasan;
use App\Models\Pengaturan;
use App\Models\TimKerja;
use App\Models\User;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KalenderController extends Controller
{
    public function __construct(
        private readonly WorkloadService $workloadService
    ) {}

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
            'user.penugasans.subKegiatan',
            'kecamatan',
            'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
        ])->whereBetween('deadline_tugas', [$startOfGrid->format('Y-m-d'), $endOfGrid->format('Y-m-d')]);

        // Query AlokasiTransport (Jadwal Turun Lapangan)
        $transportQuery = AlokasiTransport::with([
            'user.penugasans.subKegiatan',
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

        // Cache workload per user agar tidak dihitung berulang
        $userWorkloadCache = [];
        $getUserWorkload = function (?User $targetUser) use (&$userWorkloadCache, $currentMonth) {
            if (!$targetUser) {
                return null;
            }
            if (!isset($userWorkloadCache[$targetUser->id])) {
                $userWorkloadCache[$targetUser->id] = $this->workloadService->calculateUserWorkload($targetUser, $currentMonth);
            }
            return $userWorkloadCache[$targetUser->id];
        };

        // Struktur data:
        // $kegiatanByDate[dateKey][masterKegiatanKey] = [
        //     'id' => ..., 'nama' => ..., 'tahapans' => [ tahapanKey => [ ..., 'petugas' => [...] ] ]
        // ]
        $kegiatanByDate = [];

        // 1. Proses Alokasi Transport (Jadwal Turun Lapangan)
        foreach ($transportQuery->get() as $tr) {
            $dateKey = $tr->tgl_tugas->format('Y-m-d');
            $master = $tr->periodeKegiatan?->masterKegiatan;
            $masterKey = $master ? ('master_' . $master->id) : ('manual_keg_' . ($tr->periode_kegiatan_id ?? '0'));
            $namaMaster = $master?->nama ?? 'Kegiatan Lapangan';

            if (!isset($kegiatanByDate[$dateKey][$masterKey])) {
                $kegiatanByDate[$dateKey][$masterKey] = [
                    'master_id' => $master?->id,
                    'nama_kegiatan' => $namaMaster,
                    'tim_kerja' => $master?->timKerja?->nama ?? '-',
                    'tanggal' => $tr->tgl_tugas->translatedFormat('d F Y'),
                    'total_petugas_unik' => [],
                    'tahapans' => [],
                ];
            }

            $subId = $tr->penugasan?->sub_kegiatan_id ?? ('periode_' . $tr->periode_kegiatan_id);
            $tahapanKey = 'tr_' . $subId;
            $namaTahapan = $tr->penugasan?->subKegiatan?->nama ?? 'Turun Lapangan';

            if (!isset($kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey])) {
                $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey] = [
                    'nama_tahapan' => $namaTahapan,
                    'jenis' => 'Turun Lapangan (Transport)',
                    'periode' => $tr->periodeKegiatan?->nama_periode ?? '-',
                    'estimasi_jam_satuan' => (float) ($tr->penugasan?->subKegiatan?->estimasi_jam_per_satuan ?: 1),
                    'color' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'petugas' => [],
                ];
            }

            if ($tr->user) {
                $kegiatanByDate[$dateKey][$masterKey]['total_petugas_unik'][$tr->user->id] = true;
            }

            $w = $getUserWorkload($tr->user);
            $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey]['petugas'][] = [
                'nama' => $tr->user?->name ?? '-',
                'tipe' => $tr->user?->tipe ?? ($tr->user?->role ?? '-'),
                'kecamatan' => $tr->kecamatan?->nama ? ('Kec. ' . $tr->kecamatan->nama) : '-',
                'status' => $tr->penugasan?->status ?? 'Aktif',
                'info' => 'Transport: Rp ' . number_format($tr->nominal, 0, ',', '.') . ' (' . $tr->status_spj . ')',
                'workload' => $w,
            ];
        }

        // 2. Proses Penugasan (Deadline Tugas)
        foreach ($tugasQuery->get() as $t) {
            $dateKey = $t->deadline_tugas->format('Y-m-d');
            $master = $t->subKegiatan?->periodeKegiatan?->masterKegiatan;
            $masterKey = $master ? ('master_' . $master->id) : ('manual_tugas_' . ($t->sub_kegiatan_id ?? '0'));
            $namaMaster = $master?->nama ?? 'Tugas Teknis/Administrasi';

            if (!isset($kegiatanByDate[$dateKey][$masterKey])) {
                $kegiatanByDate[$dateKey][$masterKey] = [
                    'master_id' => $master?->id,
                    'nama_kegiatan' => $namaMaster,
                    'tim_kerja' => $master?->timKerja?->nama ?? '-',
                    'tanggal' => $t->deadline_tugas->translatedFormat('d F Y'),
                    'total_petugas_unik' => [],
                    'tahapans' => [],
                ];
            }

            $subId = $t->sub_kegiatan_id ?? ('sub_' . $t->id);
            $tahapanKey = 'tugas_' . $subId;
            $jenis = $t->subKegiatan?->jenis_tahapan ?? 'Teknis';
            $namaTahapan = $t->subKegiatan?->nama ?? 'Tugas';

            if (!isset($kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey])) {
                if ($jenis === 'Lapangan') {
                    $color = 'bg-amber-50 text-amber-800 border-amber-200';
                } elseif ($jenis === 'Administrasi') {
                    $color = 'bg-violet-50 text-violet-800 border-violet-200';
                } else {
                    $color = 'bg-sky-50 text-sky-800 border-sky-200';
                }

                $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey] = [
                    'nama_tahapan' => $namaTahapan,
                    'jenis' => "Tahapan {$jenis}",
                    'periode' => $t->subKegiatan?->periodeKegiatan?->nama_periode ?? '-',
                    'estimasi_jam_satuan' => (float) ($t->subKegiatan?->estimasi_jam_per_satuan ?: 1),
                    'color' => $color,
                    'petugas' => [],
                ];
            }

            if ($t->user) {
                $kegiatanByDate[$dateKey][$masterKey]['total_petugas_unik'][$t->user->id] = true;
            }

            $isOverdue = $t->status !== 'Selesai' && $t->deadline_tugas->lt(today());
            $w = $getUserWorkload($t->user);

            $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey]['petugas'][] = [
                'nama' => $t->user?->name ?? '-',
                'tipe' => $t->user?->tipe ?? ($t->user?->role ?? '-'),
                'kecamatan' => $t->kecamatan?->nama ? ('Kec. ' . $t->kecamatan->nama) : '-',
                'status' => $t->status . ($isOverdue ? ' (Overdue)' : ''),
                'target' => "{$t->realisasi_volume}/{$t->target_volume} {$t->satuan_volume} ({$t->progres_persen}%)",
                'durasi_jam' => $t->durasiJam(),
                'info' => "Waktu: {$t->durasiJam()} jam (" . ($t->subKegiatan?->estimasi_jam_per_satuan ?: 1) . " jam/satuan)",
                'workload' => $w,
            ];
        }

        // Flatten data agar rapi di Blade (list kegiatan dengan tahapans di dalamnya)
        $eventsByDate = [];
        foreach ($kegiatanByDate as $dateKey => $masters) {
            $eventsByDate[$dateKey] = array_values(array_map(function ($m) {
                $m['total_petugas'] = count($m['total_petugas_unik']);
                unset($m['total_petugas_unik']);
                $m['tahapans'] = array_values($m['tahapans']);
                return $m;
            }, $masters));
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

        // Info Kuota Kerja & SBML Bulan Ini
        $infoBulan = [
            'hari_kerja' => $this->workloadService->getHariKerjaBulan($currentMonth),
            'kuota_jam' => $this->workloadService->getTotalJamKerjaBulan($currentMonth),
            'sbml' => $this->workloadService->getSbmlMitra(),
        ];

        return view('kalender.index', compact(
            'isManager',
            'bulan',
            'currentMonth',
            'days',
            'prevBulan',
            'nextBulan',
            'timKerjas',
            'usersList',
            'kecamatans',
            'infoBulan'
        ));
    }
}
