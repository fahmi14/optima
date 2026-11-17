<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\TimKerja;
use App\Services\ExcelImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportController extends Controller
{
    public function __construct(
        private readonly ExcelImportExportService $excelService
    ) {}

    public function index(Request $request): View
    {
        $alokasis = $this->filteredQuery($request)->get();

        $totalNominal = (float) $alokasis->where('status_spj', '!=', 'Ditolak')->sum('nominal');
        $totalLayak = (float) $alokasis->whereIn('status_spj', ['Layak', 'Dibayar'])->sum('nominal');
        $totalDraft = (float) $alokasis->where('status_spj', 'Draft')->sum('nominal');

        $timKerjas = TimKerja::orderBy('nama')->get();
        $kegiatans = MasterKegiatan::orderByDesc('tahun')->orderBy('nama')->get();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('transport.index', compact(
            'alokasis',
            'totalNominal',
            'totalLayak',
            'totalDraft',
            'timKerjas',
            'kegiatans',
            'kecamatans'
        ));
    }

    public function updateStatus(Request $request, AlokasiTransport $alokasi): RedirectResponse
    {
        $data = $request->validate([
            'status_spj' => ['required', 'in:Draft,Layak,Dibayar,Ditolak'],
            'catatan_spj' => ['nullable', 'string', 'max:500'],
        ]);

        $alokasi->update($data);

        return back()->with('success', "Status SPJ {$alokasi->user?->name} diperbarui menjadi {$data['status_spj']}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $alokasis = $this->filteredQuery($request)->get();
        $filename = 'rekap_spj_transport_' . now()->format('Ymd_His') . '.xlsx';

        return $this->excelService->exportRekapTransport($alokasis, $filename);
    }

    private function filteredQuery(Request $request)
    {
        $query = AlokasiTransport::with([
            'user',
            'kecamatan',
            'penugasan.subKegiatan',
            'periodeKegiatan.masterKegiatan.timKerja',
        ])->orderByDesc('tgl_tugas');

        if ($request->filled('tim_kerja_id')) {
            $timId = $request->tim_kerja_id;
            $query->whereHas('periodeKegiatan.masterKegiatan', fn ($q) => $q->where('tim_kerja_id', $timId));
        }

        if ($request->filled('master_kegiatan_id')) {
            $masterId = $request->master_kegiatan_id;
            $query->whereHas('periodeKegiatan', fn ($q) => $q->where('master_kegiatan_id', $masterId));
        }

        if ($request->filled('kecamatan_id')) {
            $query->where('kecamatan_id', $request->kecamatan_id);
        }

        if ($request->filled('status_spj')) {
            $query->where('status_spj', $request->status_spj);
        }

        if ($request->filled('bulan')) {
            $parts = explode('-', $request->bulan);
            if (count($parts) === 2) {
                $query->whereYear('tgl_tugas', (int) $parts[0])
                    ->whereMonth('tgl_tugas', (int) $parts[1]);
            }
        }

        return $query;
    }
}
