<?php

namespace App\Http\Controllers;

use App\Models\Tim;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TimController extends Controller
{
    public function index(Request $request)
    {
        $query = Tim::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('pj', 'like', "%{$search}%");
        }
        $tims = $query->withCount('kegiatans')->latest()->paginate(10);
        return view('tim.index', compact('tims'));
    }

    public function create()
    {
        return view('tim.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'pj' => 'nullable|string|max:255',
        ]);

        Tim::create($validated);
        return redirect()->route('tim.index')->with('success', 'Data Tim berhasil ditambahkan.');
    }

    public function edit(Tim $tim)
    {
        return view('tim.edit', compact('tim'));
    }

    public function update(Request $request, Tim $tim)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'pj' => 'nullable|string|max:255',
        ]);

        $tim->update($validated);
        return redirect()->route('tim.index')->with('success', 'Data Tim berhasil diperbarui.');
    }

    public function destroy(Tim $tim)
    {
        $tim->delete();
        return redirect()->route('tim.index')->with('success', 'Data Tim berhasil dihapus.');
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tim');

        $headers = ['ID', 'Nama Tim', 'Penanggung Jawab (PJ)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        $tims = Tim::all();
        foreach ($tims as $t) {
            $sheet->setCellValue('A' . $row, $t->id);
            $sheet->setCellValue('B' . $row, $t->nama);
            $sheet->setCellValue('C' . $row, $t->pj);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'tim_export_' . date('Ymd_His') . '.xlsx';

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

            Tim::create([
                'nama' => $r[1],
                'pj' => $r[2] ?? null,
            ]);
            $count++;
        }

        return redirect()->route('tim.index')->with('success', "Berhasil mengimpor {$count} data tim dari Excel.");
    }
}
