<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\Penugasan;
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

        $zoomOptions = [1, 2, 4, 9, 12];
        $zoom = (int) $request->input('zoom', 1);
        if (!in_array($zoom, $zoomOptions, true)) {
            $zoom = 1;
        }

        $endMonth = $currentMonth->copy()->addMonths($zoom - 1)->endOfMonth();
        $rangeStart = $currentMonth->copy()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $endMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Query Penugasan yang rentangnya beririsan dengan [rangeStart, rangeEnd]
        $tugasQuery = Penugasan::with([
            'user.penugasans.subKegiatan',
            'pengawas:id,name',
            'kecamatan',
            'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
        ])->where(function ($q) use ($rangeStart, $rangeEnd) {
            $startStr = $rangeStart->format('Y-m-d');
            $endStr = $rangeEnd->format('Y-m-d');

            $q->whereHas('subKegiatan.periodeKegiatan', function ($pq) use ($startStr, $endStr) {
                $pq->whereDate('tgl_mulai', '<=', $endStr)
                   ->whereDate('tgl_selesai', '>=', $startStr);
            })->orWhereBetween('deadline_tugas', [$startStr, $endStr]);
        });

        // Query AlokasiTransport (Jadwal Turun Lapangan, kecualikan yang Ditolak)
        $transportQuery = AlokasiTransport::with([
            'user.penugasans.subKegiatan',
            'kecamatan',
            'penugasan.subKegiatan',
            'penugasan.pengawas:id,name',
            'periodeKegiatan.masterKegiatan.timKerja',
        ])
        ->where('status_spj', '!=', 'Ditolak')
        ->whereBetween('tgl_tugas', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')]);

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

        // Palette Warna Pastel untuk Legenda Kegiatan (Tahapan)
        $palette = [
            ['dot' => 'bg-indigo-400', 'bg' => 'bg-indigo-50/80', 'border' => 'border-indigo-200/80', 'text' => 'text-indigo-800'],
            ['dot' => 'bg-emerald-400', 'bg' => 'bg-emerald-50/80', 'border' => 'border-emerald-200/80', 'text' => 'text-emerald-800'],
            ['dot' => 'bg-sky-400', 'bg' => 'bg-sky-50/80', 'border' => 'border-sky-200/80', 'text' => 'text-sky-800'],
            ['dot' => 'bg-amber-400', 'bg' => 'bg-amber-50/80', 'border' => 'border-amber-200/80', 'text' => 'text-amber-800'],
            ['dot' => 'bg-rose-400', 'bg' => 'bg-rose-50/80', 'border' => 'border-rose-200/80', 'text' => 'text-rose-800'],
            ['dot' => 'bg-violet-400', 'bg' => 'bg-violet-50/80', 'border' => 'border-violet-200/80', 'text' => 'text-violet-800'],
            ['dot' => 'bg-teal-400', 'bg' => 'bg-teal-50/80', 'border' => 'border-teal-200/80', 'text' => 'text-teal-800'],
            ['dot' => 'bg-orange-400', 'bg' => 'bg-orange-50/80', 'border' => 'border-orange-200/80', 'text' => 'text-orange-800'],
            ['dot' => 'bg-cyan-400', 'bg' => 'bg-cyan-50/80', 'border' => 'border-cyan-200/80', 'text' => 'text-cyan-800'],
            ['dot' => 'bg-fuchsia-400', 'bg' => 'bg-fuchsia-50/80', 'border' => 'border-fuchsia-200/80', 'text' => 'text-fuchsia-800'],
        ];

        $legendsMap = [];
        $getLegendStyle = function (string $key, string $namaKegiatan, string $namaTahapan) use (&$legendsMap, $palette) {
            if (!isset($legendsMap[$key])) {
                $idx = count($legendsMap) % count($palette);
                $legendsMap[$key] = array_merge($palette[$idx], [
                    'key' => $key,
                    'label' => "{$namaKegiatan} ({$namaTahapan})",
                    'nama_kegiatan' => $namaKegiatan,
                    'nama_tahapan' => $namaTahapan,
                ]);
            }
            return $legendsMap[$key];
        };

        // Cache workload per user
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

        $kegiatanByDate = [];

        // 1. Proses Penugasan sebagai Rentang Tanggal (tgl_mulai periode s/d deadline_tugas/tgl_selesai)
        foreach ($tugasQuery->get() as $t) {
            $periode = $t->subKegiatan?->periodeKegiatan;
            $master = $periode?->masterKegiatan;

            $akhirRentang = $t->deadline_tugas
                ? Carbon::parse($t->deadline_tugas)->startOfDay()
                : ($periode?->tgl_selesai ? Carbon::parse($periode->tgl_selesai)->startOfDay() : null);

            if (!$akhirRentang) {
                continue;
            }

            $awalRentang = $periode?->tgl_mulai
                ? Carbon::parse($periode->tgl_mulai)->startOfDay()
                : $akhirRentang->copy();

            if ($awalRentang->gt($akhirRentang)) {
                $awalRentang = $akhirRentang->copy();
            }

            $loopStart = $awalRentang->gt($rangeStart) ? $awalRentang->copy() : $rangeStart->copy();
            $loopEnd = $akhirRentang->lt($rangeEnd) ? $akhirRentang->copy() : $rangeEnd->copy();

            if ($loopStart->gt($loopEnd)) {
                continue;
            }

            $masterKey = $master ? ('master_' . $master->id) : ('manual_tugas_' . ($t->sub_kegiatan_id ?? '0'));
            $namaMaster = $master?->nama ?? 'Kegiatan';
            $subId = $t->sub_kegiatan_id ?? ('sub_' . $t->id);
            $tahapanKey = 'tugas_' . $subId;
            $jenis = $t->subKegiatan?->jenis_tahapan ?? 'Teknis';
            $namaTahapan = $t->subKegiatan?->nama ?? 'Tugas';

            $legendKey = $masterKey . '_' . $namaTahapan;
            $style = $getLegendStyle($legendKey, $namaMaster, $namaTahapan);

            $isOverdue = $t->status !== 'Selesai' && $akhirRentang->lt(today());
            $w = $getUserWorkload($t->user);
            $satuanHonor = (float) ($t->honor_satuan ?: ($t->subKegiatan?->honor_per_satuan ?? 0));
            $honorTotal = (float) ($t->honor_total ?: ($satuanHonor * (int) $t->target_volume));
            $honorText = $honorTotal > 0 ? ' • Honor: Rp ' . number_format($honorTotal, 0, ',', '.') : '';
            $wilayahDetail = !empty($t->wilayah_tugas)
                ? implode(', ', (array) $t->wilayah_tugas)
                : ($t->kecamatan?->nama ? ('Kec. ' . $t->kecamatan->nama) : '-');

            $petugasItem = [
                'nama' => $t->user?->name ?? '-',
                'tipe' => strtolower($t->user?->tipe ?? ($t->user?->role ?? 'organik')),
                'kecamatan' => $wilayahDetail,
                'pengawas_nama' => $t->pengawas?->name,
                'status' => $t->status . ($isOverdue ? ' (Overdue)' : ''),
                'target' => "{$t->realisasi_volume}/{$t->target_volume} {$t->satuan_volume} ({$t->progres_persen}%)",
                'durasi_jam' => $t->durasiJam(),
                'info' => "Waktu: {$t->durasiJam()} jam{$honorText}",
                'workload' => $w,
            ];

            $cursor = $loopStart->copy();
            while ($cursor->lte($loopEnd)) {
                $dateKey = $cursor->format('Y-m-d');
                $isDeadline = $cursor->isSameDay($akhirRentang);

                if (!isset($kegiatanByDate[$dateKey][$masterKey])) {
                    $kegiatanByDate[$dateKey][$masterKey] = [
                        'master_id' => $master?->id,
                        'nama_kegiatan' => $namaMaster,
                        'tim_kerja' => $master?->timKerja?->nama ?? '-',
                        'tanggal' => $cursor->translatedFormat('d F Y'),
                        'rentang' => $awalRentang->format('d/m') . ' - ' . $akhirRentang->format('d/m/Y'),
                        'is_deadline' => $isDeadline,
                        'style' => $style,
                        'total_petugas_unik' => [],
                        'tahapans' => [],
                    ];
                } elseif ($isDeadline) {
                    $kegiatanByDate[$dateKey][$masterKey]['is_deadline'] = true;
                }

                if (!isset($kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey])) {
                    $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey] = [
                        'nama_tahapan' => $namaTahapan,
                        'jenis' => "Tahapan {$jenis}",
                        'periode' => $periode?->nama_periode ?? '-',
                        'estimasi_jam_satuan' => (float) ($t->subKegiatan?->estimasi_jam_per_satuan ?: 1),
                        'style' => $style,
                        'petugas' => [],
                    ];
                }

                if ($t->user) {
                    $kegiatanByDate[$dateKey][$masterKey]['total_petugas_unik'][$t->user->id] = true;
                }

                $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey]['petugas'][] = $petugasItem;
                $cursor->addDay();
            }
        }

        // 2. Proses Alokasi Transport (Jadwal Turun Lapangan di tanggal spesifik)
        foreach ($transportQuery->get() as $tr) {
            $dateKey = $tr->tgl_tugas->format('Y-m-d');
            $master = $tr->periodeKegiatan?->masterKegiatan;
            $masterKey = $master ? ('master_' . $master->id) : ('manual_keg_' . ($tr->periode_kegiatan_id ?? '0'));
            $namaMaster = $master?->nama ?? 'Kegiatan Lapangan';

            $subId = $tr->penugasan?->sub_kegiatan_id ?? ('periode_' . $tr->periode_kegiatan_id);
            $tahapanKey = 'tr_' . $subId;
            $namaTahapan = $tr->penugasan?->subKegiatan?->nama ?? 'Turun Lapangan';

            $legendKey = $masterKey . '_' . $namaTahapan;
            $style = $getLegendStyle($legendKey, $namaMaster, $namaTahapan);

            if (!isset($kegiatanByDate[$dateKey][$masterKey])) {
                $kegiatanByDate[$dateKey][$masterKey] = [
                    'master_id' => $master?->id,
                    'nama_kegiatan' => $namaMaster,
                    'tim_kerja' => $master?->timKerja?->nama ?? '-',
                    'tanggal' => $tr->tgl_tugas->translatedFormat('d F Y'),
                    'rentang' => $tr->tgl_tugas->format('d/m/Y'),
                    'is_deadline' => false,
                    'style' => $style,
                    'total_petugas_unik' => [],
                    'tahapans' => [],
                ];
            }

            if (!isset($kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey])) {
                $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey] = [
                    'nama_tahapan' => $namaTahapan,
                    'jenis' => 'Turun Lapangan (Transport)',
                    'periode' => $tr->periodeKegiatan?->nama_periode ?? '-',
                    'estimasi_jam_satuan' => (float) ($tr->penugasan?->subKegiatan?->estimasi_jam_per_satuan ?: 1),
                    'style' => $style,
                    'petugas' => [],
                ];
            }

            if ($tr->user) {
                $kegiatanByDate[$dateKey][$masterKey]['total_petugas_unik'][$tr->user->id] = true;
            }

            $w = $getUserWorkload($tr->user);
            $wilayahLabel = $tr->penugasan?->jenis_perjalanan === 'luar_kab'
                ? ('Luar Kab: ' . ($tr->penugasan?->tujuan_luar_kab ?: '-'))
                : ($tr->kecamatan?->nama ? ('Kec. ' . $tr->kecamatan->nama) : '-');

            $kegiatanByDate[$dateKey][$masterKey]['tahapans'][$tahapanKey]['petugas'][] = [
                'nama' => $tr->user?->name ?? '-',
                'tipe' => strtolower($tr->user?->tipe ?? ($tr->user?->role ?? 'organik')),
                'kecamatan' => $wilayahLabel,
                'pengawas_nama' => $tr->penugasan?->pengawas?->name,
                'status' => $tr->penugasan?->status ?? 'Aktif',
                'info' => 'Transport: Rp ' . number_format($tr->nominal, 0, ',', '.') . ' (' . $tr->status_spj . ')',
                'workload' => $w,
            ];
        }

        // Flatten data per tanggal
        $eventsByDate = [];
        foreach ($kegiatanByDate as $dateKey => $masters) {
            $eventsByDate[$dateKey] = array_values(array_map(function ($m) {
                $m['total_petugas'] = count($m['total_petugas_unik']);
                unset($m['total_petugas_unik']);
                $m['tahapans'] = array_values($m['tahapans']);
                return $m;
            }, $masters));
        }

        // Bangun daftar bulan untuk mode zoom 1, 2, 4, 9, atau 12 bulan
        $months = [];
        for ($i = 0; $i < $zoom; $i++) {
            $mCursor = $currentMonth->copy()->addMonths($i);
            $mStartGrid = $mCursor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $mEndGrid = $mCursor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $monthDays = [];
            $dCursor = $mStartGrid->copy();
            while ($dCursor->lte($mEndGrid)) {
                $key = $dCursor->format('Y-m-d');
                $monthDays[] = [
                    'date' => $key,
                    'day' => $dCursor->day,
                    'formatted_date' => $dCursor->translatedFormat('d F Y'),
                    'in_month' => $dCursor->month === $mCursor->month,
                    'is_today' => $dCursor->isToday(),
                    'events' => $eventsByDate[$key] ?? [],
                ];
                $dCursor->addDay();
            }

            $months[] = [
                'title' => $mCursor->translatedFormat('F Y'),
                'short_title' => $mCursor->translatedFormat('M Y'),
                'days' => $monthDays,
            ];
        }

        $legends = array_values($legendsMap);

        $prevBulan = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextBulan = $currentMonth->copy()->addMonth()->format('Y-m');

        $headerTitle = $zoom === 1
            ? $currentMonth->translatedFormat('F Y')
            : $currentMonth->translatedFormat('M Y') . ' – ' . $endMonth->translatedFormat('M Y');

        $timKerjas = $isManager ? TimKerja::orderBy('nama')->get() : collect();
        $usersList = $isManager ? User::whereIn('role', ['organik', 'mitra', 'katim'])->orderBy('name')->get() : collect();
        $kecamatans = $isManager ? Kecamatan::orderBy('nama')->get() : collect();

        $infoBulan = [
            'hari_kerja' => $this->workloadService->getHariKerjaBulan($currentMonth),
            'kuota_jam' => $this->workloadService->getTotalJamKerjaBulan($currentMonth),
            'sbml' => $this->workloadService->getSbmlMitra(),
        ];

        return view('kalender.index', compact(
            'isManager',
            'bulan',
            'zoom',
            'zoomOptions',
            'currentMonth',
            'headerTitle',
            'months',
            'legends',
            'prevBulan',
            'nextBulan',
            'timKerjas',
            'usersList',
            'kecamatans',
            'infoBulan'
        ));
    }
}
