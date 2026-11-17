<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Tim;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Kegiatan::with('tim');

        if ($request->filled('id_tim')) {
            $query->where('id_tim', $request->id_tim);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhereHas('tim', function($tq) use ($search) {
                      $tq->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $kegiatans = $query->latest()->paginate(10);
        $tims = Tim::all();
        return view('kegiatan.index', compact('kegiatans', 'tims'));
    }

    public function create()
    {
        $tims = Tim::all();
        return view('kegiatan.create', compact('tims'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
            'id_tim' => 'nullable|exists:tims,id',
            'honor' => 'nullable|numeric',
            'jenis_honor' => 'nullable|string|max:100',
            'sbml' => 'nullable|numeric',
            'jml_petugas' => 'nullable|integer',
            'beban' => 'nullable|integer',
            'custom_keys' => 'nullable|array',
            'custom_values' => 'nullable|array',
        ]);

        $administrasi = [
            'honor' => $request->honor,
            'jenis_honor' => $request->jenis_honor,
            'sbml' => $request->sbml,
            'jml_petugas' => $request->jml_petugas,
            'beban' => $request->beban,
        ];

        // Custom field administrasi
        if ($request->has('custom_keys') && is_array($request->custom_keys)) {
            foreach ($request->custom_keys as $index => $key) {
                if (!empty($key)) {
                    $administrasi[$key] = $request->custom_values[$index] ?? null;
                }
            }
        }

        Kegiatan::create([
            'nama' => $validated['nama'],
            'tgl_mulai' => $validated['tgl_mulai'],
            'tgl_selesai' => $validated['tgl_selesai'],
            'id_tim' => $validated['id_tim'],
            'administrasi' => $administrasi,
        ]);

        return redirect()->route('kegiatan.index')->with('success', 'Data Kegiatan berhasil ditambahkan.');
    }

    public function edit(Kegiatan $kegiatan)
    {
        $tims = Tim::all();
        return view('kegiatan.edit', compact('kegiatan', 'tims'));
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
            'id_tim' => 'nullable|exists:tims,id',
            'honor' => 'nullable|numeric',
            'jenis_honor' => 'nullable|string|max:100',
            'sbml' => 'nullable|numeric',
            'jml_petugas' => 'nullable|integer',
            'beban' => 'nullable|integer',
            'custom_keys' => 'nullable|array',
            'custom_values' => 'nullable|array',
        ]);

        $administrasi = [
            'honor' => $request->honor,
            'jenis_honor' => $request->jenis_honor,
            'sbml' => $request->sbml,
            'jml_petugas' => $request->jml_petugas,
            'beban' => $request->beban,
        ];

        if ($request->has('custom_keys') && is_array($request->custom_keys)) {
            foreach ($request->custom_keys as $index => $key) {
                if (!empty($key)) {
                    $administrasi[$key] = $request->custom_values[$index] ?? null;
                }
            }
        }

        $kegiatan->update([
            'nama' => $validated['nama'],
            'tgl_mulai' => $validated['tgl_mulai'],
            'tgl_selesai' => $validated['tgl_selesai'],
            'id_tim' => $validated['id_tim'],
            'administrasi' => $administrasi,
        ]);

        return redirect()->route('kegiatan.index')->with('success', 'Data Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        $kegiatan->delete();
        return redirect()->route('kegiatan.index')->with('success', 'Data Kegiatan berhasil dihapus.');
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kegiatan');

        $headers = [
            'ID', 'Nama Kegiatan', 'Tgl Mulai', 'Tgl Selesai', 'ID Tim', 'Nama Tim',
            'Honor', 'Jenis Honor', 'SBML', 'Jml Petugas', 'Beban', 'Administrasi (JSON)'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        $kegiatans = Kegiatan::with('tim')->get();
        foreach ($kegiatans as $k) {
            $adm = $k->administrasi ?? [];
            $sheet->setCellValue('A' . $row, $k->id);
            $sheet->setCellValue('B' . $row, $k->nama);
            $sheet->setCellValue('C' . $row, $k->tgl_mulai ? $k->tgl_mulai->format('Y-m-d') : '');
            $sheet->setCellValue('D' . $row, $k->tgl_selesai ? $k->tgl_selesai->format('Y-m-d') : '');
            $sheet->setCellValue('E' . $row, $k->id_tim);
            $sheet->setCellValue('F' . $row, $k->tim?->nama);
            $sheet->setCellValue('G' . $row, $adm['honor'] ?? '');
            $sheet->setCellValue('H' . $row, $adm['jenis_honor'] ?? '');
            $sheet->setCellValue('I' . $row, $adm['sbml'] ?? '');
            $sheet->setCellValue('J' . $row, $adm['jml_petugas'] ?? '');
            $sheet->setCellValue('K' . $row, $adm['beban'] ?? '');
            $sheet->setCellValue('L' . $row, json_encode($adm, JSON_UNESCAPED_UNICODE));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'kegiatan_export_' . date('Ymd_His') . '.xlsx';

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

        $count = 0;
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            if (empty($r[1])) continue;

            $administrasi = [];
            if (!empty($r[11])) {
                $decoded = json_decode($r[11], true);
                if (is_array($decoded)) {
                    $administrasi = $decoded;
                }
            } else {
                $administrasi = [
                    'honor' => !empty($r[6]) ? (float)$r[6] : null,
                    'jenis_honor' => $r[7] ?? null,
                    'sbml' => !empty($r[8]) ? (float)$r[8] : null,
                    'jml_petugas' => !empty($r[9]) ? (int)$r[9] : null,
                    'beban' => !empty($r[10]) ? (int)$r[10] : null,
                ];
            }

            Kegiatan::create([
                'nama' => $r[1],
                'tgl_mulai' => !empty($r[2]) ? date('Y-m-d', strtotime($r[2])) : null,
                'tgl_selesai' => !empty($r[3]) ? date('Y-m-d', strtotime($r[3])) : null,
                'id_tim' => !empty($r[4]) ? (int)$r[4] : null,
                'administrasi' => $administrasi,
            ]);
            $count++;
        }

        return redirect()->route('kegiatan.index')->with('success', "Berhasil mengimpor {$count} data kegiatan dari Excel.");
    }
}
