<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Nks;
use App\Models\Pengaturan;
use App\Models\Penugasan;
use App\Models\Sls;
use App\Models\SubKegiatan;
use App\Models\SubSls;
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

    /**
     * Download template Alokasi Wilayah berisi email, nama (sesuai petugas terpilih), dan id_wilayah (kosong)
     * + Sheet 2 berisi Referensi ID Wilayah sesuai level wilayah yang dipilih.
     */
    public function downloadAlokasiTemplate(Request $request, SubKegiatan $sub): StreamedResponse
    {
        $idsRaw = (string) $request->query('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
        $level = $request->query('level', 'kecamatan');
        if (!in_array($level, ['kecamatan', 'desa', 'sls', 'sub_sls', 'nks'], true)) {
            $level = 'kecamatan';
        }

        $users = !empty($ids)
            ? User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Sheet 1: Alokasi Petugas
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Alokasi Petugas');
        $headers1 = ['email', 'nama', 'id_wilayah'];
        foreach ($headers1 as $colIdx => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$col}1", $h);
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
        ]);

        foreach ($users as $idx => $u) {
            $row = $idx + 2;
            $sheet1->setCellValue("A{$row}", $u->email);
            $sheet1->setCellValue("B{$row}", $u->name);
            $sheet1->setCellValue("C{$row}", '');
        }

        // Sheet 2: Referensi Wilayah sesuai level terpilih
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Referensi Wilayah');
        $headers2 = ['id_wilayah', 'kode', 'nama_wilayah', 'kecamatan', 'desa'];
        foreach ($headers2 as $colIdx => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet2->setCellValue("{$col}1", $h);
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '312E81'],
            ],
        ]);

        $refRows = [];
        if ($level === 'kecamatan') {
            foreach (Kecamatan::orderBy('kode')->get() as $k) {
                $refRows[] = [$k->id, $k->kode, "Kec. {$k->nama}", $k->nama, '-'];
            }
        } elseif ($level === 'desa') {
            foreach (Desa::with('kecamatan')->orderBy('id')->get() as $d) {
                $refRows[] = [$d->id, $d->kd_desa ?? $d->id, $d->desa, $d->kecamatan?->nama ?? '-', $d->desa];
            }
        } elseif ($level === 'sls') {
            foreach (Sls::with(['kecamatan', 'desa'])->orderBy('id')->get() as $s) {
                $refRows[] = [$s->id, $s->id_sls ?? $s->id, $s->sls, $s->kecamatan?->nama ?? '-', $s->desa?->desa ?? '-'];
            }
        } elseif ($level === 'sub_sls') {
            foreach (SubSls::with(['kecamatan', 'desa'])->orderBy('id')->get() as $ss) {
                $refRows[] = [$ss->id, $ss->id_sls ?? $ss->id, $ss->subsls, $ss->kecamatan?->nama ?? '-', $ss->desa?->desa ?? '-'];
            }
        } elseif ($level === 'nks') {
            foreach (Nks::with(['kecamatan', 'desa'])->orderBy('id')->get() as $n) {
                $refRows[] = [$n->id, $n->nks, "NKS {$n->nks}" . ($n->sls ? " - {$n->sls}" : ''), $n->kecamatan?->nama ?? '-', $n->desa?->desa ?? '-'];
            }
        }

        foreach ($refRows as $idx => $r) {
            $row = $idx + 2;
            $sheet2->setCellValue("A{$row}", $r[0]);
            $sheet2->setCellValue("B{$row}", $r[1]);
            $sheet2->setCellValue("C{$row}", $r[2]);
            $sheet2->setCellValue("D{$row}", $r[3]);
            $sheet2->setCellValue("E{$row}", $r[4]);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "template_alokasi_{$level}_sub{$sub->id}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import Excel Alokasi Wilayah (email, nama, id_wilayah) untuk Langkah 3 Wizard
     */
    public function importAlokasi(Request $request, SubKegiatan $sub): JsonResponse
    {
        $request->validate([
            'level' => ['required', 'in:kecamatan,desa,sls,sub_sls,nks'],
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:10240'],
        ]);

        $level = $request->input('level');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getRealPath());
        $raw = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($raw) < 2) {
            return response()->json([
                'matched' => [],
                'success_rows' => 0,
                'errors' => ['File Excel kosong atau tidak memiliki data.'],
            ]);
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $raw[0]);
        $matched = [];
        $successRows = 0;
        $errors = [];

        for ($i = 1; $i < count($raw); $i++) {
            $line = $raw[$i];
            $nonEmpty = array_filter($line, fn ($v) => $v !== null && trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            $rowNum = $i + 1;
            $assoc = [];
            foreach ($headers as $colIdx => $key) {
                if ($key !== '') {
                    $assoc[$key] = $line[$colIdx] ?? null;
                }
            }

            $email = mb_strtolower(trim((string) ($assoc['email'] ?? '')));
            $nama = trim((string) ($assoc['nama'] ?? ''));
            $idWilayah = trim((string) ($assoc['id_wilayah'] ?? ''));

            if ($email === '' && $nama === '') {
                $errors[] = "Baris {$rowNum}: Email atau nama wajib diisi.";
                continue;
            }

            if ($idWilayah === '') {
                $errors[] = "Baris {$rowNum} ({$nama}): id_wilayah masih kosong.";
                continue;
            }

            $user = $email !== '' ? User::where('email', $email)->first() : null;
            if (!$user && $nama !== '') {
                $user = User::where('name', $nama)->first();
            }

            if (!$user) {
                $errors[] = "Baris {$rowNum}: Petugas '{$email}' / '{$nama}' tidak ditemukan.";
                continue;
            }

            $wilayahValue = null;
            $kecamatanId = null;

            if ($level === 'kecamatan') {
                $k = is_numeric($idWilayah)
                    ? (Kecamatan::find((int) $idWilayah) ?? Kecamatan::where('kode', $idWilayah)->first())
                    : Kecamatan::where('kode', $idWilayah)->orWhere('nama', $idWilayah)->first();
                if ($k) {
                    $wilayahValue = "Kec. {$k->nama}";
                    $kecamatanId = $k->id;
                }
            } elseif ($level === 'desa') {
                $d = Desa::with('kecamatan')->find((int) $idWilayah);
                if ($d) {
                    $kecNama = $d->kecamatan?->nama ?? '-';
                    $wilayahValue = "{$d->desa} (Kec {$kecNama})";
                    $kecamatanId = $d->id_kec;
                }
            } elseif ($level === 'sls') {
                $s = Sls::with(['kecamatan', 'desa'])
                    ->where('id', (int) $idWilayah)
                    ->orWhere('id_sls', $idWilayah)
                    ->first();
                if ($s) {
                    $kecNama = $s->kecamatan?->nama ?? '-';
                    $desaNama = $s->desa?->desa ?? '-';
                    $wilayahValue = "{$s->sls} (Kec {$kecNama}, Desa {$desaNama})";
                    $kecamatanId = $s->id_kec;
                }
            } elseif ($level === 'sub_sls') {
                $ss = SubSls::with(['kecamatan', 'desa'])
                    ->where('id', (int) $idWilayah)
                    ->orWhere('id_sls', $idWilayah)
                    ->first();
                if ($ss) {
                    $kecNama = $ss->kecamatan?->nama ?? '-';
                    $desaNama = $ss->desa?->desa ?? '-';
                    $wilayahValue = "{$ss->subsls} (Kec {$kecNama}, Desa {$desaNama})";
                    $kecamatanId = $ss->id_kec;
                }
            } elseif ($level === 'nks') {
                $n = Nks::with(['kecamatan', 'desa'])
                    ->where('id', (int) $idWilayah)
                    ->orWhere('nks', $idWilayah)
                    ->first();
                if ($n) {
                    $kecNama = $n->kecamatan?->nama ?? '-';
                    $desaNama = $n->desa?->desa ?? '-';
                    $slsText = $n->sls ? " - {$n->sls}" : '';
                    $wilayahValue = "NKS {$n->nks}{$slsText} (Kec {$kecNama}, Desa {$desaNama})";
                    $kecamatanId = $n->id_kec;
                }
            }

            if (!$wilayahValue) {
                $errors[] = "Baris {$rowNum} ({$user->name}): id_wilayah '{$idWilayah}' tidak ditemukan pada level {$level}.";
                continue;
            }

            if (!isset($matched[$user->id])) {
                $matched[$user->id] = [
                    'kecamatan_id' => $kecamatanId,
                    'wilayah_tugas' => [],
                ];
            }
            if (!in_array($wilayahValue, $matched[$user->id]['wilayah_tugas'], true)) {
                $matched[$user->id]['wilayah_tugas'][] = $wilayahValue;
            }
            $successRows++;
        }

        return response()->json([
            'matched' => $matched,
            'success_rows' => $successRows,
            'errors' => $errors,
        ]);
    }
}
