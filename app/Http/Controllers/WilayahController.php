<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WilayahController extends Controller
{
    public function index(Request $request)
    {
        $query = Wilayah::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kab', 'like', "%{$search}%")
                  ->orWhere('kec', 'like', "%{$search}%")
                  ->orWhere('desa', 'like', "%{$search}%")
                  ->orWhere('sls', 'like', "%{$search}%");
            });
        }
        $wilayahs = $query->latest()->paginate(10);
        return view('wilayah.index', compact('wilayahs'));
    }

    public function create()
    {
        return view('wilayah.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kab' => 'nullable|string|max:255',
            'kd_kab' => 'nullable|string|max:50',
            'kec' => 'nullable|string|max:255',
            'kd_kec' => 'nullable|string|max:50',
            'desa' => 'nullable|string|max:255',
            'kd_desa' => 'nullable|string|max:50',
            'sls' => 'nullable|string|max:255',
            'kd_sls' => 'nullable|string|max:50',
            'subsls' => 'nullable|string|max:50',
        ]);

        Wilayah::create($validated);
        return redirect()->route('wilayah.index')->with('success', 'Data Wilayah berhasil ditambahkan.');
    }

    public function edit(Wilayah $wilayah)
    {
        return view('wilayah.edit', compact('wilayah'));
    }

    public function update(Request $request, Wilayah $wilayah)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kab' => 'nullable|string|max:255',
            'kd_kab' => 'nullable|string|max:50',
            'kec' => 'nullable|string|max:255',
            'kd_kec' => 'nullable|string|max:50',
            'desa' => 'nullable|string|max:255',
            'kd_desa' => 'nullable|string|max:50',
            'sls' => 'nullable|string|max:255',
            'kd_sls' => 'nullable|string|max:50',
            'subsls' => 'nullable|string|max:50',
        ]);

        $wilayah->update($validated);
        return redirect()->route('wilayah.index')->with('success', 'Data Wilayah berhasil diperbarui.');
    }

    public function destroy(Wilayah $wilayah)
    {
        $wilayah->delete();
        return redirect()->route('wilayah.index')->with('success', 'Data Wilayah berhasil dihapus.');
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wilayah');

        $headers = ['ID', 'Nama Wilayah', 'Kabupaten', 'Kd Kab', 'Kecamatan', 'Kd Kec', 'Desa/Kelurahan', 'Kd Desa', 'SLS', 'Kd SLS', 'Sub SLS'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        $wilayahs = Wilayah::all();
        foreach ($wilayahs as $w) {
            $sheet->setCellValue('A' . $row, $w->id);
            $sheet->setCellValue('B' . $row, $w->nama);
            $sheet->setCellValue('C' . $row, $w->kab);
            $sheet->setCellValue('D' . $row, $w->kd_kab);
            $sheet->setCellValue('E' . $row, $w->kec);
            $sheet->setCellValue('F' . $row, $w->kd_kec);
            $sheet->setCellValue('G' . $row, $w->desa);
            $sheet->setCellValue('H' . $row, $w->kd_desa);
            $sheet->setCellValue('I' . $row, $w->sls);
            $sheet->setCellValue('J' . $row, $w->kd_sls);
            $sheet->setCellValue('K' . $row, $w->subsls);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'wilayah_export_' . date('Ymd_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Lewati header (row index 0)
        $count = 0;
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            if (empty($r[1]) && empty($r[0])) continue; // lewati baris kosong
            
            // Kolom: [0 => ID, 1 => nama, 2 => kab, 3 => kd_kab, 4 => kec, 5 => kd_kec, 6 => desa, 7 => kd_desa, 8 => sls, 9 => kd_sls, 10 => subsls]
            Wilayah::create([
                'nama' => $r[1] ?? 'Wilayah Baru',
                'kab' => $r[2] ?? null,
                'kd_kab' => $r[3] ?? null,
                'kec' => $r[4] ?? null,
                'kd_kec' => $r[5] ?? null,
                'desa' => $r[6] ?? null,
                'kd_desa' => $r[7] ?? null,
                'sls' => $r[8] ?? null,
                'kd_sls' => $r[9] ?? null,
                'subsls' => $r[10] ?? null,
            ]);
            $count++;
        }

        return redirect()->route('wilayah.index')->with('success', "Berhasil mengimpor {$count} data wilayah dari Excel.");
    }
}
