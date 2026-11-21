<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\Penugasan;
use App\Models\TimKerja;
use App\Models\User;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WorkloadService $workloadService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['organik', 'mitra'])) {
            $tugasAktif = Penugasan::with([
                'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
                'kecamatan',
                'alokasiTransports',
            ])
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Selesai')
                ->orderByRaw('deadline_tugas IS NULL, deadline_tugas ASC')
                ->get();

            $totalTugasAktif = $tugasAktif->count();
            $tugasMendekatiDeadline = $tugasAktif->filter(function ($t) {
                return $t->deadline_tugas
                    && $t->deadline_tugas->isBetween(today(), today()->addDays(3));
            })->count();
            $tugasOverdue = $tugasAktif->filter(function ($t) {
                return $t->deadline_tugas && $t->deadline_tugas->lt(today());
            })->count();

            $workload = $this->workloadService->calculateUserWorkload($user);
            $totalHakTransport = (float) $user->alokasiTransports()
                ->where('status_spj', '!=', 'Ditolak')
                ->sum('nominal');

            return view('dashboard-pegawai', compact(
                'tugasAktif',
                'totalTugasAktif',
                'tugasMendekatiDeadline',
                'tugasOverdue',
                'workload',
                'totalHakTransport'
            ));
        }

        $timKerjaId = $request->filled('tim_kerja_id') ? (int) $request->tim_kerja_id : null;
        $kecamatanId = $request->filled('kecamatan_id') ? (int) $request->kecamatan_id : null;
        $bulanFilter = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->bulan) : now();

        $startOfMonth = $bulanFilter->copy()->startOfMonth();
        $endOfMonth = $bulanFilter->copy()->endOfMonth();

        $totalTugasAktif = Penugasan::where('status', '!=', 'Selesai')->count();

        $tugasMendekatiDeadline = Penugasan::where('status', '!=', 'Selesai')
            ->whereDate('deadline_tugas', '>=', today())
            ->whereDate('deadline_tugas', '<=', today()->addDays(3))
            ->count();

        $tugasOverdue = Penugasan::where('status', '!=', 'Selesai')
            ->whereDate('deadline_tugas', '<', today())
            ->count();

        $totalSerapanTransport = (float) AlokasiTransport::where('status_spj', '!=', 'Ditolak')->sum('nominal');

        $sbmlLimit = $this->workloadService->getSbmlMitra();
        $kuotaJamBulan = $this->workloadService->getTotalJamKerjaBulan($bulanFilter);

        $organikCount = User::where('tipe', 'organik')
            ->when($timKerjaId, fn($q) => $q->where('tim_kerja_id', $timKerjaId))
            ->count();
        $mitraCount = User::where('tipe', 'mitra')->count();

        // 3. JADWAL TURUN LAPANGAN GROUPING PER KEGIATAN DENGAN INFO PETUGAS & WILAYAH
        $jadwalQuery = AlokasiTransport::with([
            'user',
            'kecamatan',
            'periodeKegiatan.masterKegiatan.timKerja',
            'penugasan.subKegiatan',
            'penugasan.pengawas:id,name',
        ])
        ->where('status_spj', '!=', 'Ditolak')
        ->whereBetween('tgl_tugas', [$startOfMonth, $endOfMonth])
        ->orderBy('tgl_tugas');

        if ($kecamatanId) {
            $jadwalQuery->where('kecamatan_id', $kecamatanId);
        }

        $allJadwal = $jadwalQuery->get();

        // Grouping per kegiatan (Master Kegiatan + Nama Tahapan)
        $groupedJadwalLapangan = $allJadwal->groupBy(function ($item) {
            $keg = $item->periodeKegiatan?->masterKegiatan?->nama ?? 'Kegiatan Lapangan';
            $tahap = $item->penugasan?->subKegiatan?->nama ?? 'Pendataan';
            return "{$keg} - {$tahap}";
        })->map(function ($items, $key) {
            return [
                'nama_group' => $key,
                'total_turlap' => $items->count(),
                'items' => $items,
            ];
        });

        $kegiatans = MasterKegiatan::with([
            'timKerja',
            'periodes.subKegiatans.penugasans',
            'periodes.alokasiTransports',
        ])->latest()->get();

        $timKerjas = TimKerja::orderBy('nama')->get();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('dashboard', compact(
            'totalTugasAktif',
            'tugasMendekatiDeadline',
            'tugasOverdue',
            'totalSerapanTransport',
            'organikCount',
            'mitraCount',
            'groupedJadwalLapangan',
            'sbmlLimit',
            'kuotaJamBulan',
            'kegiatans',
            'timKerjas',
            'kecamatans',
            'timKerjaId',
            'kecamatanId',
            'bulanFilter'
        ));
    }

    public function bebanOrganikData(Request $request): JsonResponse
    {
        return $this->buildBebanData($request, 'organik');
    }

    public function bebanMitraData(Request $request): JsonResponse
    {
        return $this->buildBebanData($request, 'mitra');
    }

    private function buildBebanData(Request $request, string $tipe): JsonResponse
    {
        $timKerjaId = $request->filled('tim_kerja_id') ? (int) $request->tim_kerja_id : null;
        $bulanFilter = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->bulan) : now();

        $users = User::with(['timKerja', 'kecamatan', 'penugasans.subKegiatan'])
            ->where('tipe', $tipe)
            ->when($tipe === 'organik' && $timKerjaId, fn ($q) => $q->where('tim_kerja_id', $timKerjaId))
            ->get();

        $rows = $users->map(function (User $u) use ($bulanFilter, $tipe) {
            $w = $this->workloadService->calculateUserWorkload($u, $bulanFilter);

            return [
                'id' => $u->id,
                'nama' => $u->name,
                'id_sobat' => $u->id_sobat ?? '-',
                'info_unit' => $tipe === 'organik'
                    ? ($u->timKerja?->nama ?? 'Lintas Tim')
                    : ('Kec. ' . ($u->kecamatan?->nama ?? '-')),
                'jumlah_tugas_aktif' => (int) $w['jumlah_tugas_aktif'],
                'total_jam' => (float) $w['total_jam'],
                'kuota_jam' => (float) $w['kuota_jam'],
                'persentase_jam' => (float) $w['persentase_jam'],
                'bar_persen' => (float) $w['bar_persen'],
                'bar_class' => $w['bar_class'],
                'badge_class' => $w['badge_class'],
                'status' => $w['status'],
            ];
        })->sortByDesc('total_jam')->values();

        return DataTables::collection($rows)->toJson();
    }

    public function alokasiHonorData(Request $request): JsonResponse
    {
        $bulanFilter = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->bulan) : now();
        $startOfMonth = $bulanFilter->copy()->startOfMonth();
        $endOfMonth = $bulanFilter->copy()->endOfMonth();
        $sbmlLimit = $this->workloadService->getSbmlMitra();

        $mitraUsers = User::with([
            'kecamatan',
            'penugasans.subKegiatan.periodeKegiatan.masterKegiatan',
        ])
            ->where('tipe', 'mitra')
            ->where(function ($uq) use ($startOfMonth, $endOfMonth) {
                $uq->whereHas('penugasans', function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->where(function ($subQ) use ($startOfMonth, $endOfMonth) {
                        $subQ->whereBetween('deadline_tugas', [$startOfMonth, $endOfMonth])
                            ->orWhere(function ($activeQ) {
                                $activeQ->whereNull('deadline_tugas')->where('status', '!=', 'Selesai');
                            });
                    });
                })->orWhereHas('alokasiTransports', function ($tq) use ($startOfMonth, $endOfMonth) {
                    $tq->where('status_spj', '!=', 'Ditolak')
                        ->whereBetween('tgl_tugas', [$startOfMonth, $endOfMonth]);
                });
            })
            ->get();

        $rows = $mitraUsers->map(function (User $u) use ($bulanFilter, $startOfMonth, $endOfMonth, $sbmlLimit) {
            $w = $this->workloadService->calculateUserWorkload($u, $bulanFilter);

            $tasks = $u->penugasans->filter(function ($t) use ($startOfMonth, $endOfMonth) {
                if ($t->deadline_tugas) {
                    return $t->deadline_tugas->between($startOfMonth, $endOfMonth);
                }
                return $t->status !== 'Selesai';
            })->map(function ($t) {
                $satuanHonor = (float) ($t->honor_satuan ?: ($t->subKegiatan?->honor_per_satuan ?? 0));
                $honorTotal = (float) ($t->honor_total ?: ($satuanHonor * (int) $t->target_volume));
                return [
                    'kegiatan' => $t->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama ?? 'Kegiatan',
                    'tahapan' => $t->subKegiatan?->nama ?? 'Tahapan',
                    'target' => $t->target_volume . ' ' . $t->satuan_volume,
                    'honor' => $honorTotal,
                    'deadline' => $t->deadline_tugas ? $t->deadline_tugas->format('d/m/Y') : '-',
                    'status' => $t->status,
                ];
            })->values();

            $isOverSbml = $w['total_honor'] > $sbmlLimit;
            $isWarningSbml = !$isOverSbml && $w['persentase_honor'] >= 80;

            return [
                'id' => $u->id,
                'nama' => $u->name,
                'id_sobat' => $u->id_sobat ?? '-',
                'kecamatan_nama' => $u->kecamatan?->nama ?? '-',
                'jumlah_tahapan' => $tasks->count(),
                'total_honor' => (float) $w['total_honor'],
                'persentase_honor' => (float) $w['persentase_honor'],
                'is_over_sbml' => $isOverSbml,
                'is_warning_sbml' => $isWarningSbml,
                'selisih_sbml' => $isOverSbml ? ($w['total_honor'] - $sbmlLimit) : 0,
                'detail_payload' => [
                    'user' => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'id_sobat' => $u->id_sobat,
                        'kecamatan' => $u->kecamatan ? ['nama' => $u->kecamatan->nama] : null,
                    ],
                    'workload' => $w,
                    'tasks' => $tasks,
                ],
            ];
        })->sortByDesc('total_honor')->values();

        return DataTables::collection($rows)->toJson();
    }
}
