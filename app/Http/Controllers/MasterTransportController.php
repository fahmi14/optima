<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterTransportController extends Controller
{
    public function index(Request $request): View
    {
        $asalId = $request->query('asal_id');
        $tujuanId = $request->query('tujuan_id');
        $viewMode = $request->query('mode', 'list'); // list | matrix

        $kecamatans = Kecamatan::orderBy('kode')->orderBy('nama')->get();

        $transports = MasterTransport::with(['kecamatanAsal', 'kecamatanTujuan'])
            ->when($asalId, fn ($q) => $q->where('kecamatan_asal_id', $asalId))
            ->when($tujuanId, fn ($q) => $q->where('kecamatan_tujuan_id', $tujuanId))
            ->orderBy('kecamatan_asal_id')
            ->orderBy('kecamatan_tujuan_id')
            ->paginate(30)
            ->withQueryString();

        // Untuk mode matriks 15x15
        $matrixData = [];
        if ($viewMode === 'matrix') {
            $all = MasterTransport::all();
            foreach ($all as $item) {
                $matrixData[$item->kecamatan_asal_id][$item->kecamatan_tujuan_id] = (float) $item->nominal;
            }
        }

        $maksDalamKab = (float) Pengaturan::get('maks_transport_dalam_kab', 170000);
        $tarifLuarKab = (float) Pengaturan::get('transport_luar_kab', 370000);

        return view('master-transport.index', compact(
            'transports',
            'kecamatans',
            'asalId',
            'tujuanId',
            'viewMode',
            'matrixData',
            'maksDalamKab',
            'tarifLuarKab'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_asal_id' => ['required', 'exists:kecamatans,id'],
            'kecamatan_tujuan_id' => ['required', 'exists:kecamatans,id'],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        MasterTransport::updateOrCreate(
            [
                'kecamatan_asal_id' => $data['kecamatan_asal_id'],
                'kecamatan_tujuan_id' => $data['kecamatan_tujuan_id'],
            ],
            ['nominal' => $data['nominal']]
        );

        return back()->with('success', 'Tarif transport rute berhasil disimpan.');
    }

    public function update(Request $request, MasterTransport $masterTransport): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_asal_id' => ['required', 'exists:kecamatans,id'],
            'kecamatan_tujuan_id' => ['required', 'exists:kecamatans,id'],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $masterTransport->update($data);

        return back()->with('success', 'Tarif transport berhasil diperbarui.');
    }

    public function destroy(MasterTransport $masterTransport): RedirectResponse
    {
        $masterTransport->delete();

        return back()->with('success', 'Data tarif transport berhasil dihapus.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'maks_transport_dalam_kab' => ['required', 'numeric', 'min:0'],
            'transport_luar_kab' => ['required', 'numeric', 'min:0'],
        ]);

        Pengaturan::updateOrCreate(
            ['kunci' => 'maks_transport_dalam_kab'],
            [
                'label' => 'Batas Maksimal Transport Lokal Dalam Kabupaten per Hari (Rp)',
                'nilai' => (string) $data['maks_transport_dalam_kab'],
            ]
        );

        Pengaturan::updateOrCreate(
            ['kunci' => 'transport_luar_kab'],
            [
                'label' => 'Tarif Perjalanan Dinas Luar Kabupaten per Hari (Rp)',
                'nilai' => (string) $data['transport_luar_kab'],
            ]
        );

        return back()->with('success', 'Pengaturan batas maksimal dalam kabupaten & tarif luar kabupaten berhasil diperbarui.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Master Transport');

        $sheet->fromArray([
            ['id_kec_asal', 'kecamatan_asal', 'id_kec_tujuan', 'kecamatan_tujuan', 'nominal'],
            ['11', 'Wonosobo', '15', 'Kejajar', '130000'],
            ['11', 'Wonosobo', '14', 'Garung', '100000'],
        ]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_master_transport.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $kecById = Kecamatan::all()->keyBy('id');
        $kecByName = Kecamatan::all()->keyBy(fn ($k) => strtolower(trim($k->nama)));

        $count = 0;
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            if (empty(array_filter($r))) continue;

            $asalId = is_numeric($r[0] ?? null) ? (int) $r[0] : null;
            if (!$asalId || !$kecById->has($asalId)) {
                $asalName = strtolower(trim((string) ($r[1] ?? '')));
                $asalId = $kecByName->get($asalName)?->id;
            }

            $tujuanId = is_numeric($r[2] ?? null) ? (int) $r[2] : null;
            if (!$tujuanId || !$kecById->has($tujuanId)) {
                $tujuanName = strtolower(trim((string) ($r[3] ?? '')));
                $tujuanId = $kecByName->get($tujuanName)?->id;
            }

            $nominalRaw = preg_replace('/[^0-9.]/', '', (string) ($r[4] ?? '0'));
            $nominal = (float) $nominalRaw;

            if (!$asalId || !$tujuanId) continue;

            MasterTransport::updateOrCreate(
                [
                    'kecamatan_asal_id' => $asalId,
                    'kecamatan_tujuan_id' => $tujuanId,
                ],
                ['nominal' => $nominal]
            );
            $count++;
        }

        return back()->with('success', "Berhasil mengimpor {$count} rute tarif Master Transport.");
    }
}
