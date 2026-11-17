<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\Penugasan;
use App\Models\TimKerja;
use App\Services\WorkloadService;
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
        $tipe = $request->filled('tipe') ? $request->tipe : null;
        $kecamatanId = $request->filled('kecamatan_id') ? (int) $request->kecamatan_id : null;

        $totalTugasAktif = Penugasan::where('status', '!=', 'Selesai')->count();

        $tugasMendekatiDeadline = Penugasan::where('status', '!=', 'Selesai')
            ->whereDate('deadline_tugas', '>=', today())
            ->whereDate('deadline_tugas', '<=', today()->addDays(3))
            ->count();

        $tugasOverdue = Penugasan::where('status', '!=', 'Selesai')
            ->whereDate('deadline_tugas', '<', today())
            ->count();

        $totalSerapanTransport = (float) AlokasiTransport::where('status_spj', '!=', 'Ditolak')->sum('nominal');

        $workloadMap = $this->workloadService->getWorkloadMap($timKerjaId, $tipe);

        $jadwalQuery = AlokasiTransport::with([
            'user',
            'kecamatan',
            'periodeKegiatan.masterKegiatan.timKerja',
            'penugasan.subKegiatan',
        ])->orderBy('tgl_tugas');

        if ($kecamatanId) {
            $jadwalQuery->where('kecamatan_id', $kecamatanId);
        }

        $jadwalLapangan = $jadwalQuery->take(20)->get();

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
            'workloadMap',
            'jadwalLapangan',
            'kegiatans',
            'timKerjas',
            'kecamatans',
            'timKerjaId',
            'tipe',
            'kecamatanId'
        ));
    }
}
