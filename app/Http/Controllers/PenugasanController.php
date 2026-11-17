<?php

namespace App\Http\Controllers;

use App\Models\Penugasan;
use App\Models\PeriodeKegiatan;
use App\Models\SubKegiatan;
use App\Services\ExcelImportExportService;
use App\Services\TransportValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenugasanController extends Controller
{
    public function __construct(
        private readonly TransportValidationService $transportService,
        private readonly ExcelImportExportService $excelService
    ) {}

    public function store(Request $request, SubKegiatan $sub): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
            'target_volume' => ['required', 'integer', 'min:1'],
            'satuan_volume' => ['required', 'string', 'max:50'],
            'bobot_poin' => ['nullable', 'integer'],
            'estimasi_jam_satuan' => ['nullable', 'numeric', 'min:0.1'],
            'honor_satuan' => ['nullable', 'numeric', 'min:0'],
            'deadline_tugas' => ['nullable', 'date'],
            'dapat_transport' => ['nullable', 'boolean'],
            'tgl_tugas_transport' => ['nullable', 'string'],
            'nominal_transport' => ['nullable', 'numeric', 'min:0'],
        ]);

        $jamPerSatuan = (float) ($request->estimasi_jam_satuan ?: ($sub->estimasi_jam_per_satuan ?: 1));
        $honorSatuan = (float) ($request->honor_satuan ?: ($sub->honor_per_satuan ?: 0));
        $targetVol = (int) $data['target_volume'];
        $estimasiJamTotal = round($jamPerSatuan * $targetVol, 2);
        $honorTotal = round($honorSatuan * $targetVol, 2);

        $needTransport = $request->boolean('dapat_transport');
        $dates = [];
        if ($needTransport) {
            $rawDates = array_filter(array_map('trim', explode(',', (string) $request->tgl_tugas_transport)));
            if (empty($rawDates)) {
                return back()->withInput()->with('error', 'Tanggal tugas transport wajib diisi jika transport diaktifkan.');
            }
            $dates = $rawDates;
        }

        try {
            DB::transaction(function () use ($sub, $data, $needTransport, $dates, $request, $estimasiJamTotal, $honorSatuan, $honorTotal) {
                $penugasan = Penugasan::create([
                    'sub_kegiatan_id' => $sub->id,
                    'user_id' => $data['user_id'],
                    'kecamatan_id' => $data['kecamatan_id'] ?? null,
                    'target_volume' => $data['target_volume'],
                    'satuan_volume' => $data['satuan_volume'],
                    'bobot_poin' => (int) round($estimasiJamTotal),
                    'estimasi_jam_total' => $estimasiJamTotal,
                    'honor_satuan' => $honorSatuan,
                    'honor_total' => $honorTotal,
                    'deadline_tugas' => $data['deadline_tugas'] ?? $sub->deadline_tahapan,
                    'status' => 'Belum Mulai',
                ]);

                if ($needTransport) {
                    $this->transportService->allocateForPenugasan(
                        $penugasan,
                        $dates,
                        (float) ($request->nominal_transport ?? 0),
                        $data['kecamatan_id'] ?? null
                    );
                }
            });

            return back()->with('success', "Penugasan berhasil didistribusikan ({$estimasiJamTotal} jam kerja).");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function verify(Request $request, Penugasan $penugasan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:Selesai,Revisi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $penugasan->update([
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? $penugasan->catatan,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', "Status tugas diperbarui menjadi {$data['status']}.");
    }

    public function destroy(Penugasan $penugasan): RedirectResponse
    {
        $penugasan->delete();

        return back()->with('success', 'Penugasan berhasil dihapus.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->excelService->downloadTemplatePenugasan();
    }

    public function import(Request $request, PeriodeKegiatan $periode): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:5120'],
        ]);

        $report = $this->excelService->importPenugasan($request->file('file'), $periode);

        return back()->with('import_report', [
            'title' => "Laporan Import Penugasan ({$periode->nama_periode})",
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }
}
