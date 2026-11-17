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
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // 1. BEBAN PEGAWAI ORGANIK (DITAMPILKAN SEMUA, DIURUTKAN BEBAN TERBESAR BULAN INI)
        $organikUsers = User::with(['timKerja', 'kecamatan'])
            ->where('tipe', 'organik')
            ->when($timKerjaId, fn($q) => $q->where('tim_kerja_id', $timKerjaId))
            ->get();

        $pegawaiBebanList = $organikUsers->map(function (User $u) use ($bulanFilter) {
            $w = $this->workloadService->calculateUserWorkload($u, $bulanFilter);
            return [
                'user' => $u,
                'workload' => $w,
            ];
        })->sortByDesc(fn($item) => $item['workload']['total_jam'])->values();

        // 2. BEBAN MITRA STATISTIK (HANYA YANG SUDAH TERALOKASIKAN KE KEGIATAN PADA BULAN INI)
        // Optimasi: Hanya query user mitra yang memiliki penugasan di bulan yang bersangkutan
        $mitraUsers = User::with(['timKerja', 'kecamatan'])
            ->where('tipe', 'mitra')
            ->whereHas('penugasans', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where(function ($subQ) use ($startOfMonth, $endOfMonth) {
                    $subQ->whereBetween('deadline_tugas', [$startOfMonth, $endOfMonth])
                         ->orWhere(function ($activeQ) {
                             $activeQ->whereNull('deadline_tugas')->where('status', '!=', 'Selesai');
                         });
                });
            })
            ->get();

        $sbmlLimit = $this->workloadService->getSbmlMitra();
        $kuotaJamBulan = $this->workloadService->getTotalJamKerjaBulan($bulanFilter);

        $mitraBebanList = $mitraUsers->map(function (User $u) use ($bulanFilter, $startOfMonth, $endOfMonth) {
            $w = $this->workloadService->calculateUserWorkload($u, $bulanFilter);

            // Ambil daftar penugasan detail mitra untuk modal rincian kegiatan & honor
            $tasks = Penugasan::with('subKegiatan.periodeKegiatan.masterKegiatan')
                ->where('user_id', $u->id)
                ->where(function ($subQ) use ($startOfMonth, $endOfMonth) {
                    $subQ->whereBetween('deadline_tugas', [$startOfMonth, $endOfMonth])
                         ->orWhere(function ($activeQ) {
                             $activeQ->whereNull('deadline_tugas')->where('status', '!=', 'Selesai');
                         });
                })
                ->get()
                ->map(function ($t) {
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
                });

            return [
                'user' => $u,
                'workload' => $w,
                'tasks' => $tasks,
            ];
        })->sortByDesc(fn($item) => $item['workload']['total_honor'])->values();

        // 3. JADWAL TURUN LAPANGAN GROUPING PER KEGIATAN DENGAN INFO PETUGAS & WILAYAH
        $jadwalQuery = AlokasiTransport::with([
            'user',
            'kecamatan',
            'periodeKegiatan.masterKegiatan.timKerja',
            'penugasan.subKegiatan',
        ])
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
            'pegawaiBebanList',
            'mitraBebanList',
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
}
