<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Penugasan;
use App\Models\SubKegiatan;
use App\Models\User;
use App\Services\ExcelImportExportService;
use App\Services\TransportValidationService;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenugasanWizardController extends Controller
{
    public function __construct(
        private readonly WorkloadService $workloadService,
        private readonly TransportValidationService $transportService,
        private readonly ExcelImportExportService $excelService
    ) {}

    /**
     * Endpoint Server-Side Data Petugas (Yajra Datatables compatible)
     * Mengembalikan data petugas per halaman dengan search & filter cepat tanpa beban DOM 1000+ baris
     */
    public function petugasData(Request $request, SubKegiatan $sub): JsonResponse
    {
        $sub->load('periodeKegiatan.masterKegiatan');
        $periode = $sub->periodeKegiatan;
        $tahun = (int) ($periode->masterKegiatan?->tahun ?? date('Y'));
        $monthDate = $sub->deadline_tahapan ? Carbon::parse($sub->deadline_tahapan) : ($periode->tgl_mulai ? Carbon::parse($periode->tgl_mulai) : now());

        $tipe = $request->query('tipe', 'all');
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(5, min(100, (int) $request->query('per_page', 15)));

        $query = User::with(['timKerja', 'kecamatan'])
            ->where(function ($q) use ($tahun) {
                $q->where(function ($org) {
                    $org->where('tipe', 'organik')
                        ->whereIn('role', ['organik', 'katim', 'keuangan', 'admin', 'pimpinan']);
                })->orWhere(function ($mit) use ($tahun) {
                    $mit->where('tipe', 'mitra')
                        ->where(function ($mq) use ($tahun) {
                            $mq->whereHas('mitraKepkas', fn($kq) => $kq->where('tahun', $tahun))
                               ->orWhereDoesntHave('mitraKepkas'); // Fallback jika belum diimport
                        });
                });
            });

        if ($tipe !== 'all') {
            $query->where('tipe', $tipe);
        }

        if ($search !== '') {
            $query->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                   ->orWhere('nip', 'like', "%{$search}%")
                   ->orWhere('id_sobat', 'like', "%{$search}%")
                   ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$search}%"));
            });
        }

        $totalFiltered = $query->count();
        $users = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $users->map(function (User $u) use ($monthDate) {
            $wl = $this->workloadService->calculateUserWorkload($u, $monthDate);
            return [
                'id' => $u->id,
                'name' => $u->name,
                'tipe' => $u->tipe,
                'role' => $u->role,
                'role_label' => $u->roleLabel(),
                'nip_sobat' => $u->nip ?: ($u->id_sobat ?: '-'),
                'tim' => $u->timKerja?->nama ?? '-',
                'kecamatan_id' => $u->kecamatan_id,
                'kecamatan_nama' => $u->kecamatan?->nama ?? '-',
                'workload' => $wl,
            ];
        });

        return response()->json([
            'draw' => (int) $request->query('draw', 1),
            'recordsTotal' => $totalFiltered,
            'recordsFiltered' => $totalFiltered,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($totalFiltered / $perPage),
            'data' => $data,
        ]);
    }

    /**
     * Endpoint Fetch Detail Petugas yang Dicentang (Agar saat ke Step 3 datanya lengkap)
     */
    public function selectedPetugasDetails(Request $request, SubKegiatan $sub): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        if (empty($ids)) {
            return response()->json([]);
        }

        $monthDate = $sub->deadline_tahapan ? Carbon::parse($sub->deadline_tahapan) : ($sub->periodeKegiatan?->tgl_mulai ? Carbon::parse($sub->periodeKegiatan->tgl_mulai) : now());

        $users = User::with(['timKerja', 'kecamatan'])
            ->whereIn('id', $ids)
            ->get();

        $data = $users->map(function (User $u) use ($monthDate) {
            $wl = $this->workloadService->calculateUserWorkload($u, $monthDate);
            return [
                'id' => $u->id,
                'name' => $u->name,
                'tipe' => $u->tipe,
                'role' => $u->role,
                'role_label' => $u->roleLabel(),
                'nip_sobat' => $u->nip ?: ($u->id_sobat ?: '-'),
                'tim' => $u->timKerja?->nama ?? '-',
                'kecamatan_id' => $u->kecamatan_id,
                'kecamatan_nama' => $u->kecamatan?->nama ?? '-',
                'workload' => $wl,
            ];
        });

        return response()->json($data);
    }

    /**
     * Halaman Utama Wizard Distribusi Penugasan
     */
    public function create(SubKegiatan $sub): View
    {
        $sub->load('periodeKegiatan.masterKegiatan');
        $periode = $sub->periodeKegiatan;
        $tahun = (int) ($periode->masterKegiatan?->tahun ?? date('Y'));
        $monthDate = $sub->deadline_tahapan ? Carbon::parse($sub->deadline_tahapan) : ($periode->tgl_mulai ? Carbon::parse($periode->tgl_mulai) : now());

        $kecamatans = Kecamatan::orderBy('nama')->get();

        $infoBulan = [
            'nama_bulan' => $monthDate->translatedFormat('F Y'),
            'hari_kerja' => $this->workloadService->getHariKerjaBulan($monthDate),
            'kuota_jam' => $this->workloadService->getTotalJamKerjaBulan($monthDate),
            'sbml' => $this->workloadService->getSbmlMitra(),
        ];

        // Ambil daftar calon pengawas (pegawai organik atau pimpinan/katim)
        $pengawasCandidates = User::where('tipe', 'organik')
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'role']);

        return view('penugasan.wizard', compact(
            'sub',
            'periode',
            'pengawasCandidates',
            'kecamatans',
            'infoBulan',
            'tahun'
        ));
    }

    /**
     * Simpan distribusi tugas batch (banyak petugas sekaligus)
     */
    public function storeBatch(Request $request, SubKegiatan $sub): RedirectResponse
    {
        $data = $request->validate([
            'level_wilayah' => ['nullable', 'string', 'in:kecamatan,desa,sls,sub_sls,nks'],
            'ada_pengawas' => ['nullable', 'boolean'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.user_id' => ['required', 'exists:users,id'],
            'assignments.*.pengawas_id' => ['nullable', 'exists:users,id'],
            'assignments.*.kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
            'assignments.*.wilayah_tugas' => ['nullable', 'array'],
            'assignments.*.wilayah_tugas.*' => ['nullable', 'string'],
            'assignments.*.target_volume' => ['required', 'integer', 'min:1'],
            'assignments.*.satuan_volume' => ['required', 'string', 'max:50'],
            'assignments.*.estimasi_jam_per_satuan' => ['required', 'numeric', 'min:0.1'],
            'assignments.*.honor_per_satuan' => ['nullable', 'numeric', 'min:0'],
            'assignments.*.deadline_tugas' => ['nullable', 'date'],
            'assignments.*.dapat_transport' => ['nullable', 'boolean'],
            'assignments.*.tgl_tugas_transport' => ['nullable', 'string'],
            'assignments.*.nominal_transport' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($sub, $data) {
                $levelWilayah = $data['level_wilayah'] ?? 'kecamatan';
                $adaPengawas = !empty($data['ada_pengawas']);

                $sub->update([
                    'level_wilayah' => $levelWilayah,
                    'ada_pengawas' => $adaPengawas,
                ]);

                foreach ($data['assignments'] as $item) {
                    $targetVol = (int) $item['target_volume'];
                    $jamPerSatuan = (float) $item['estimasi_jam_per_satuan'];
                    $honorPerSatuan = (float) ($item['honor_per_satuan'] ?? 0);

                    $jamTotal = round($targetVol * $jamPerSatuan, 2);
                    $honorTotal = round($targetVol * $honorPerSatuan, 2);

                    $rawWilayah = $item['wilayah_tugas'] ?? [];
                    $cleanedWilayah = array_values(array_filter(array_map('trim', (array) $rawWilayah)));

                    $penugasan = Penugasan::create([
                        'sub_kegiatan_id' => $sub->id,
                        'user_id' => $item['user_id'],
                        'pengawas_id' => $adaPengawas ? ($item['pengawas_id'] ?? null) : null,
                        'kecamatan_id' => $item['kecamatan_id'] ?? null,
                        'level_wilayah' => $levelWilayah,
                        'wilayah_tugas' => $cleanedWilayah,
                        'target_volume' => $targetVol,
                        'satuan_volume' => $item['satuan_volume'] ?? 'Dokumen',
                        'bobot_poin' => (int) round($jamTotal),
                        'estimasi_jam_total' => $jamTotal,
                        'honor_satuan' => $honorPerSatuan,
                        'honor_total' => $honorTotal,
                        'deadline_tugas' => $item['deadline_tugas'] ?? $sub->deadline_tahapan,
                        'status' => 'Belum Mulai',
                    ]);

                    if (!empty($item['dapat_transport']) && !empty($item['tgl_tugas_transport'])) {
                        $dates = array_filter(array_map('trim', explode(',', (string) $item['tgl_tugas_transport'])));
                        if (!empty($dates)) {
                            $this->transportService->allocateForPenugasan(
                                $penugasan,
                                $dates,
                                (float) ($item['nominal_transport'] ?? 150000),
                                $item['kecamatan_id'] ?? null
                            );
                        }
                    }
                }
            });

            return redirect()->route('kegiatan.periode.show', $sub->periode_kegiatan_id)
                ->with('success', 'Berhasil mendistribusikan penugasan kepada ' . count($data['assignments']) . ' petugas.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk wizard
     */
    public function downloadTemplate(): StreamedResponse
    {
        return $this->excelService->downloadTemplatePenugasan();
    }
}
