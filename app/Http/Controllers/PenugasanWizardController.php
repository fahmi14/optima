<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Pengaturan;
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
                            $mq->whereHas('masterMitra.alokasiKepkas', fn($kq) => $kq->where('tahun', $tahun))
                               ->orWhereDoesntHave('masterMitra'); // Fallback jika belum diimport
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
        $defaultWonosoboId = $kecamatans->first(fn ($k) => strtolower(trim($k->nama)) === 'wonosobo')?->id ?? '';

        $transportMatrix = [];
        foreach (MasterTransport::all() as $mt) {
            $transportMatrix[$mt->kecamatan_asal_id][$mt->kecamatan_tujuan_id] = (float) $mt->nominal;
        }

        $maksDalamKab = (float) Pengaturan::get('maks_transport_dalam_kab', 170000);
        $tarifLuarKab = (float) Pengaturan::get('transport_luar_kab', 370000);

        $infoBulan = [
            'nama_bulan' => $monthDate->translatedFormat('F Y'),
            'hari_kerja' => $this->workloadService->getHariKerjaBulan($monthDate),
            'kuota_jam' => $this->workloadService->getTotalJamKerjaBulan($monthDate),
            'sbml' => $this->workloadService->getSbmlMitra(),
        ];

        // Ambil daftar calon pengawas (pegawai organik DAN mitra KEPKA tahun kegiatan)
        $pengawasOrganik = User::with('kecamatan')
            ->where('tipe', 'organik')
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'id_sobat', 'role', 'tipe', 'kecamatan_id']);

        $pengawasMitra = User::with('kecamatan')
            ->where('tipe', 'mitra')
            ->whereHas('masterMitra.alokasiKepkas', fn ($q) => $q->where('tahun', $tahun))
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'id_sobat', 'role', 'tipe', 'kecamatan_id']);

        if ($pengawasMitra->isEmpty()) {
            $pengawasMitra = User::with('kecamatan')
                ->where('tipe', 'mitra')
                ->orderBy('name')
                ->get(['id', 'name', 'nip', 'id_sobat', 'role', 'tipe', 'kecamatan_id']);
        }

        $pengawasCandidates = $pengawasOrganik->concat($pengawasMitra)->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'tipe' => $u->tipe,
                'role_label' => $u->roleLabel(),
                'kecamatan_id' => $u->kecamatan_id,
                'kecamatan_nama' => $u->kecamatan?->nama ?? '-',
            ];
        })->values();

        return view('penugasan.wizard', compact(
            'sub',
            'periode',
            'pengawasCandidates',
            'kecamatans',
            'defaultWonosoboId',
            'transportMatrix',
            'maksDalamKab',
            'tarifLuarKab',
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
            'assignments.*.jenis_perjalanan' => ['nullable', 'string', 'in:dalam_kab,luar_kab'],
            'assignments.*.asal_perjalanan' => ['nullable', 'string', 'max:100'],
            'assignments.*.tujuan_luar_kab' => ['nullable', 'string', 'max:150'],
            'assignments.*.rute_transport' => ['nullable', 'array'],
            'assignments.*.wilayah_tugas' => ['nullable', 'array'],
            'assignments.*.wilayah_tugas.*' => ['nullable', 'string'],
            'assignments.*.target_volume' => ['required', 'integer', 'min:1'],
            'assignments.*.satuan_volume' => ['required', 'string', 'max:50'],
            'assignments.*.estimasi_jam_per_satuan' => ['required', 'numeric', 'min:0.1'],
            'assignments.*.honor_per_satuan' => ['nullable', 'numeric', 'min:0'],
            'assignments.*.deadline_tugas' => ['nullable', 'date'],
            'assignments.*.dapat_transport' => ['nullable', 'boolean'],
            'assignments.*.jumlah_hari_transport' => ['nullable', 'integer', 'min:1'],
            'assignments.*.tgl_tugas_transport' => ['nullable', 'string'],
            'assignments.*.nominal_transport' => ['nullable', 'numeric', 'min:0'],

            // Pengaturan honor & transport khusus Pengawas (Langkah 5)
            'pengawas_assignments' => ['nullable', 'array'],
            'pengawas_assignments.*.user_id' => ['required_with:pengawas_assignments', 'exists:users,id'],
            'pengawas_assignments.*.kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
            'pengawas_assignments.*.target_volume' => ['nullable', 'integer', 'min:1'],
            'pengawas_assignments.*.satuan_volume' => ['nullable', 'string', 'max:50'],
            'pengawas_assignments.*.estimasi_jam_per_satuan' => ['nullable', 'numeric', 'min:0.1'],
            'pengawas_assignments.*.honor_per_satuan' => ['nullable', 'numeric', 'min:0'],
            'pengawas_assignments.*.deadline_tugas' => ['nullable', 'date'],
            'pengawas_assignments.*.wilayah_tugas' => ['nullable', 'string'],
            'pengawas_assignments.*.dapat_transport' => ['nullable', 'boolean'],
            'pengawas_assignments.*.jumlah_hari_transport' => ['nullable', 'integer', 'min:1'],
            'pengawas_assignments.*.jenis_perjalanan' => ['nullable', 'string', 'in:dalam_kab,luar_kab'],
            'pengawas_assignments.*.asal_perjalanan' => ['nullable', 'string', 'max:100'],
            'pengawas_assignments.*.tujuan_luar_kab' => ['nullable', 'string', 'max:150'],
            'pengawas_assignments.*.rute_transport' => ['nullable', 'array'],
            'pengawas_assignments.*.tgl_tugas_transport' => ['nullable', 'string'],
            'pengawas_assignments.*.nominal_transport' => ['nullable', 'numeric', 'min:0'],
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

                    $jenisPerjalanan = $item['jenis_perjalanan'] ?? 'dalam_kab';
                    $asalPerjalanan = $item['asal_perjalanan'] ?? ($jenisPerjalanan === 'luar_kab' ? 'Kabupaten Wonosobo' : 'Kecamatan Wonosobo');
                    $ruteTransport = array_values(array_filter((array) ($item['rute_transport'] ?? [])));

                    $jumlahHari = !empty($item['dapat_transport']) ? max(1, (int) ($item['jumlah_hari_transport'] ?? 1)) : 0;

                    $penugasan = Penugasan::create([
                        'sub_kegiatan_id' => $sub->id,
                        'user_id' => $item['user_id'],
                        'pengawas_id' => $adaPengawas ? ($item['pengawas_id'] ?? null) : null,
                        'kecamatan_id' => $item['kecamatan_id'] ?? null,
                        'jenis_perjalanan' => $jenisPerjalanan,
                        'asal_perjalanan' => $asalPerjalanan,
                        'tujuan_luar_kab' => $jenisPerjalanan === 'luar_kab' ? ($item['tujuan_luar_kab'] ?? null) : null,
                        'rute_transport' => $jenisPerjalanan === 'dalam_kab' ? $ruteTransport : null,
                        'jumlah_hari_transport' => $jumlahHari,
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

                    if (!empty($item['dapat_transport'])) {
                        $dates = !empty($item['tgl_tugas_transport'])
                            ? array_filter(array_map('trim', explode(',', (string) $item['tgl_tugas_transport'])))
                            : [];
                        $this->transportService->allocateFlexibleForPenugasan(
                            $penugasan,
                            $jumlahHari,
                            $dates,
                            (float) ($item['nominal_transport'] ?? 0),
                            [
                                'jenis_perjalanan' => $jenisPerjalanan,
                                'asal_perjalanan' => $asalPerjalanan,
                                'tujuan_luar_kab' => $item['tujuan_luar_kab'] ?? null,
                                'rute_kecamatan_ids' => $ruteTransport,
                                'kecamatan_id' => $item['kecamatan_id'] ?? null,
                            ]
                        );
                    }
                }

                // Jika ada role pengawas dan pengawas_assignments dikirim (Langkah 5):
                // Otomatis tambahkan tahapan baru "Pengawasan" pada periode ini berisi para pengawas
                if ($adaPengawas && !empty($data['pengawas_assignments'])) {
                    $namaTahapanPengawasan = stripos($sub->nama, 'pengawasan') !== false
                        ? $sub->nama
                        : 'Pengawasan - ' . $sub->nama;

                    $subPengawasan = SubKegiatan::firstOrCreate(
                        [
                            'periode_kegiatan_id' => $sub->periode_kegiatan_id,
                            'nama' => $namaTahapanPengawasan,
                        ],
                        [
                            'jenis_tahapan' => 'Lapangan',
                            'level_wilayah' => $levelWilayah,
                            'ada_pengawas' => false,
                            'deadline_tahapan' => $sub->deadline_tahapan,
                            'urutan' => $sub->periodeKegiatan->subKegiatans()->count() + 1,
                            'estimasi_jam_per_satuan' => $sub->estimasi_jam_per_satuan ?: 7,
                            'honor_per_satuan' => $sub->honor_per_satuan ?: 0,
                        ]
                    );

                    foreach ($data['pengawas_assignments'] as $pmlItem) {
                        if (empty($pmlItem['user_id'])) continue;

                        $pmlVol = max(1, (int) ($pmlItem['target_volume'] ?? 1));
                        $pmlJamSatuan = (float) ($pmlItem['estimasi_jam_per_satuan'] ?? ($sub->estimasi_jam_per_satuan ?: 7));
                        $pmlHonorSatuan = (float) ($pmlItem['honor_per_satuan'] ?? 0);
                        $pmlJamTotal = round($pmlVol * $pmlJamSatuan, 2);
                        $pmlHonorTotal = round($pmlVol * $pmlHonorSatuan, 2);

                        $pmlJenisPerjalanan = $pmlItem['jenis_perjalanan'] ?? 'dalam_kab';
                        $pmlAsal = $pmlItem['asal_perjalanan'] ?? ($pmlJenisPerjalanan === 'luar_kab' ? 'Kabupaten Wonosobo' : 'Kecamatan Wonosobo');
                        $pmlRute = array_values(array_filter((array) ($pmlItem['rute_transport'] ?? [])));

                        $pmlWilayah = !empty($pmlItem['wilayah_tugas'])
                            ? array_values(array_filter(array_map('trim', explode(',', (string) $pmlItem['wilayah_tugas']))))
                            : [];

                        $pmlJumlahHari = !empty($pmlItem['dapat_transport']) ? max(1, (int) ($pmlItem['jumlah_hari_transport'] ?? 1)) : 0;

                        $pmlPenugasan = Penugasan::create([
                            'sub_kegiatan_id' => $subPengawasan->id,
                            'user_id' => $pmlItem['user_id'],
                            'kecamatan_id' => $pmlItem['kecamatan_id'] ?? null,
                            'jenis_perjalanan' => $pmlJenisPerjalanan,
                            'asal_perjalanan' => $pmlAsal,
                            'tujuan_luar_kab' => $pmlJenisPerjalanan === 'luar_kab' ? ($pmlItem['tujuan_luar_kab'] ?? null) : null,
                            'rute_transport' => $pmlJenisPerjalanan === 'dalam_kab' ? $pmlRute : null,
                            'jumlah_hari_transport' => $pmlJumlahHari,
                            'level_wilayah' => $levelWilayah,
                            'wilayah_tugas' => $pmlWilayah,
                            'target_volume' => $pmlVol,
                            'satuan_volume' => $pmlItem['satuan_volume'] ?? 'Petugas',
                            'bobot_poin' => (int) round($pmlJamTotal),
                            'estimasi_jam_total' => $pmlJamTotal,
                            'honor_satuan' => $pmlHonorSatuan,
                            'honor_total' => $pmlHonorTotal,
                            'deadline_tugas' => $pmlItem['deadline_tugas'] ?? $sub->deadline_tahapan,
                            'status' => 'Belum Mulai',
                        ]);

                        if (!empty($pmlItem['dapat_transport'])) {
                            $pmlDates = !empty($pmlItem['tgl_tugas_transport'])
                                ? array_filter(array_map('trim', explode(',', (string) $pmlItem['tgl_tugas_transport'])))
                                : [];
                            $this->transportService->allocateFlexibleForPenugasan(
                                $pmlPenugasan,
                                $pmlJumlahHari,
                                $pmlDates,
                                (float) ($pmlItem['nominal_transport'] ?? 0),
                                [
                                    'jenis_perjalanan' => $pmlJenisPerjalanan,
                                    'asal_perjalanan' => $pmlAsal,
                                    'tujuan_luar_kab' => $pmlItem['tujuan_luar_kab'] ?? null,
                                    'rute_kecamatan_ids' => $pmlRute,
                                    'kecamatan_id' => $pmlItem['kecamatan_id'] ?? null,
                                ]
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
