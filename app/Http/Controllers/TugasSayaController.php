<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Pengaturan;
use App\Models\Penugasan;
use App\Services\TransportValidationService;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TugasSayaController extends Controller
{
    public function __construct(
        private readonly WorkloadService $workloadService,
        private readonly TransportValidationService $transportService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Penugasan::with([
            'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
            'kecamatan',
            'alokasiTransports' => fn ($q) => $q->orderBy('hari_ke')->orderBy('tgl_tugas'),
        ])->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $penugasans = $query->orderByRaw("CASE status 
                WHEN 'Revisi' THEN 1 
                WHEN 'Berjalan' THEN 2 
                WHEN 'Belum Mulai' THEN 3 
                WHEN 'Menunggu Verifikasi' THEN 4 
                WHEN 'Selesai' THEN 5 
                ELSE 6 
            END")
            ->orderBy('deadline_tugas')
            ->get();

        $workload = $this->workloadService->calculateUserWorkload($user);
        $totalHakTransport = (float) $user->alokasiTransports()
            ->where('status_spj', '!=', 'Ditolak')
            ->sum('nominal');

        $kecamatans = Kecamatan::orderBy('nama')->get();
        $defaultWonosoboId = $kecamatans->first(fn ($k) => strtolower(trim($k->nama)) === 'wonosobo')?->id ?? '';

        $transportMatrix = [];
        foreach (MasterTransport::all() as $mt) {
            $transportMatrix[$mt->kecamatan_asal_id][$mt->kecamatan_tujuan_id] = (float) $mt->nominal;
        }

        $maksDalamKab = (float) Pengaturan::get('maks_transport_dalam_kab', 170000);
        $tarifLuarKab = (float) Pengaturan::get('transport_luar_kab', 370000);

        return view('tugas-saya.index', compact(
            'penugasans',
            'workload',
            'totalHakTransport',
            'kecamatans',
            'defaultWonosoboId',
            'transportMatrix',
            'maksDalamKab',
            'tarifLuarKab'
        ));
    }

    public function updateProgress(Request $request, Penugasan $penugasan): RedirectResponse
    {
        if ($penugasan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mengubah tugas ini.');
        }

        // Cek apakah masih ada alokasi translok yang wajib diisi tujuannya
        $belumIsiTranslok = $penugasan->alokasiTransports()
            ->where('status_spj', '!=', 'Ditolak')
            ->where(function ($q) {
                $q->where('tujuan_terisi', false)->orWhereNull('tgl_tugas');
            })
            ->exists();

        if ($belumIsiTranslok) {
            return back()->with('error', 'Harap lengkapi Tanggal & Lokasi Tujuan Translok terlebih dahulu sebelum memperbarui progres tugas.');
        }

        $data = $request->validate([
            'realisasi_volume' => ['required', 'integer', 'min:0'],
            'progres_persen' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:Belum Mulai,Berjalan,Menunggu Verifikasi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'file_bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $filePath = $penugasan->file_bukti;
        if ($request->hasFile('file_bukti')) {
            $uploadDir = public_path('uploads/bukti');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->file('file_bukti');
            $filename = 'bukti_' . $penugasan->id . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $filePath = 'uploads/bukti/' . $filename;
        }

        $penugasan->update([
            'realisasi_volume' => $data['realisasi_volume'],
            'progres_persen' => $data['progres_persen'],
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? $penugasan->catatan,
            'file_bukti' => $filePath,
        ]);

        return back()->with('success', 'Progres tugas dan bukti pelaksanaan berhasil disimpan.');
    }

    /**
     * Petugas mengisi/melengkapi tujuan & tanggal translok (per hari, bisa > 1 kecamatan dalam 1 hari).
     */
    public function updateTranslok(Request $request, AlokasiTransport $alokasi): RedirectResponse
    {
        if ($alokasi->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mengubah alokasi translok ini.');
        }

        $data = $request->validate([
            'tgl_tugas' => ['required', 'date'],
            'jenis_perjalanan' => ['required', 'in:dalam_kab,luar_kab'],
            'asal_kec_id' => ['nullable', 'exists:kecamatans,id'],
            'asal_perjalanan' => ['nullable', 'string', 'max:100'],
            'rute_kecamatan_ids' => ['required_if:jenis_perjalanan,dalam_kab', 'array'],
            'rute_kecamatan_ids.*' => ['nullable', 'exists:kecamatans,id'],
            'tujuan_luar_kab' => ['required_if:jenis_perjalanan,luar_kab', 'nullable', 'string', 'max:150'],
        ]);

        $tglNormalized = Carbon::parse($data['tgl_tugas'])->format('Y-m-d');

        // 1. Cek bentrok Anti-Double SPJ untuk tanggal tersebut (selain record ini sendiri)
        $conflict = AlokasiTransport::with('periodeKegiatan.masterKegiatan')
            ->where('user_id', auth()->id())
            ->where('id', '!=', $alokasi->id)
            ->whereDate('tgl_tugas', $tglNormalized)
            ->first();

        if ($conflict) {
            $kegStr = $conflict->periodeKegiatan?->masterKegiatan?->nama ?? 'kegiatan lain';
            return back()->with('error', "Tanggal {$tglNormalized} sudah memiliki alokasi SPJ pada {$kegStr} (Anti-Double SPJ).");
        }

        $ruteIds = array_values(array_filter(array_map('intval', (array) ($data['rute_kecamatan_ids'] ?? []))));
        if ($data['jenis_perjalanan'] === 'dalam_kab' && empty($ruteIds)) {
            return back()->with('error', 'Minimal pilih 1 kecamatan tujuan untuk perjalanan dalam kabupaten.');
        }

        $nominal = $this->transportService->calculateNominalFromMaster(
            $data['jenis_perjalanan'],
            !empty($data['asal_kec_id']) ? (int) $data['asal_kec_id'] : null,
            $ruteIds
        );

        // 2. Cek Sisa Pagu Periode
        $periode = $alokasi->periodeKegiatan;
        if ($periode && (float) $periode->pagu_transport > 0) {
            $terpakaiLain = (float) AlokasiTransport::where('periode_kegiatan_id', $periode->id)
                ->where('id', '!=', $alokasi->id)
                ->where('status_spj', '!=', 'Ditolak')
                ->sum('nominal');
            if (($terpakaiLain + $nominal) > (float) $periode->pagu_transport) {
                $sisa = max(0, (float) $periode->pagu_transport - $terpakaiLain);
                return back()->with('error', 'Melebihi sisa pagu transport periode (Sisa: Rp ' . number_format($sisa, 0, ',', '.') . ').');
            }
        }

        $asalText = $data['jenis_perjalanan'] === 'luar_kab'
            ? ($data['asal_perjalanan'] ?: 'Kabupaten Wonosobo')
            : ('Kec. ' . (Kecamatan::find($data['asal_kec_id'] ?? null)?->nama ?? 'Wonosobo'));

        $alokasi->update([
            'tgl_tugas' => $tglNormalized,
            'jenis_perjalanan' => $data['jenis_perjalanan'],
            'asal_perjalanan' => $asalText,
            'tujuan_luar_kab' => $data['jenis_perjalanan'] === 'luar_kab' ? $data['tujuan_luar_kab'] : null,
            'rute_kecamatan_ids' => $data['jenis_perjalanan'] === 'dalam_kab' ? $ruteIds : null,
            'kecamatan_id' => $data['jenis_perjalanan'] === 'dalam_kab' && !empty($ruteIds) ? end($ruteIds) : null,
            'nominal' => $nominal,
            'tujuan_terisi' => true,
        ]);

        return back()->with('success', 'Tujuan & tanggal translok Hari ke-' . $alokasi->hari_ke . ' berhasil disimpan (Rp ' . number_format($nominal, 0, ',', '.') . ').');
    }
}
