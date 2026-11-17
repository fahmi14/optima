<?php

namespace App\Http\Controllers;

use App\Models\MatrikTugas;
use App\Models\Kegiatan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MatrikTugasController extends Controller
{
    public function index(Request $request)
    {
        $query = MatrikTugas::with(['kegiatan', 'user.organik', 'user.mitra', 'pengawas', 'wilayah']);

        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', $request->id_kegiatan);
        }

        if ($request->filled('id_user')) {
            $query->where('id_user', $request->id_user);
        }

        if ($request->filled('id_wilayah')) {
            $query->where('id_wilayah', $request->id_wilayah);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('kegiatan', function($kq) use ($search) {
                    $kq->where('nama', 'like', "%{$search}%");
                })->orWhereHas('user', function($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('pengawas', function($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%");
                })->orWhereHas('wilayah', function($wq) use ($search) {
                    $wq->where('nama', 'like', "%{$search}%");
                });
            });
        }

        $matriks = $query->latest()->paginate(10);
        $kegiatans = Kegiatan::all();
        $users = User::all();
        $wilayahs = Wilayah::all();

        return view('matrik.index', compact('matriks', 'kegiatans', 'users', 'wilayahs'));
    }

    public function create()
    {
        $kegiatans = Kegiatan::all();
        $users = User::with(['organik', 'mitra'])->orderBy('name')->get();
        $wilayahs = Wilayah::orderBy('nama')->get();
        return view('matrik.create', compact('kegiatans', 'users', 'wilayahs'));
    }

    public function store(Request $request)
    {
        // Mendukung pembuatan alokasi multi-petugas dan multi-wilayah sesuai alur step 1-7
        $request->validate([
            'id_kegiatan' => 'required|exists:kegiatans,id',
            'allocations' => 'required|array|min:1',
            'allocations.*.id_user' => 'required|exists:users,id',
            'allocations.*.id_wilayah' => 'required|exists:wilayahs,id',
            'allocations.*.beban' => 'required|integer|min:0',
            'allocations.*.id_pengawas' => 'nullable|exists:users,id',
            'allocations.*.role' => 'nullable|string',
            'allocations.*.tgl' => 'nullable|date',
        ]);

        $createdCount = 0;
        $tglGlobal = $request->input('tgl_global');

        foreach ($request->allocations as $alloc) {
            $role = !empty($alloc['role']) ? [trim($alloc['role'])] : ['PCL (Pencacah Lapangan)'];
            $idPengawas = !empty($alloc['id_pengawas']) ? $alloc['id_pengawas'] : null;

            MatrikTugas::create([
                'id_kegiatan' => $request->id_kegiatan,
                'id_user' => $alloc['id_user'],
                'id_pengawas' => $idPengawas,
                'role' => $role,
                'id_wilayah' => $alloc['id_wilayah'],
                'tgl' => $alloc['tgl'] ?? $tglGlobal ?? null,
                'beban' => $alloc['beban'] ?? 0,
            ]);
            $createdCount++;
        }

        return redirect()->route('matrik.index')->with('success', "Berhasil menambahkan {$createdCount} alokasi matrik penugasan.");
    }

    public function edit(MatrikTugas $matrik)
    {
        $kegiatans = Kegiatan::all();
        $users = User::with(['organik', 'mitra'])->orderBy('name')->get();
        $wilayahs = Wilayah::orderBy('nama')->get();
        return view('matrik.edit', compact('matrik', 'kegiatans', 'users', 'wilayahs'));
    }

    public function update(Request $request, MatrikTugas $matrik)
    {
        $validated = $request->validate([
            'id_kegiatan' => 'required|exists:kegiatans,id',
            'id_user' => 'required|exists:users,id',
            'id_pengawas' => 'nullable|exists:users,id',
            'role' => 'nullable|array',
            'custom_role' => 'nullable|string',
            'id_wilayah' => 'nullable|exists:wilayahs,id',
            'tgl' => 'nullable|date',
            'beban' => 'required|integer|min:0',
        ]);

        $roles = $request->role ?? [];
        if (!empty($request->custom_role)) {
            $roles[] = trim($request->custom_role);
        }

        $matrik->update([
            'id_kegiatan' => $validated['id_kegiatan'],
            'id_user' => $validated['id_user'],
            'id_pengawas' => $validated['id_pengawas'] ?? null,
            'role' => array_values(array_unique($roles)),
            'id_wilayah' => $validated['id_wilayah'],
            'tgl' => $validated['tgl'],
            'beban' => $validated['beban'],
        ]);

        return redirect()->route('matrik.index')->with('success', 'Data Matrik Tugas berhasil diperbarui.');
    }

    public function destroy(MatrikTugas $matrik)
    {
        $matrik->delete();
        return redirect()->route('matrik.index')->with('success', 'Data Matrik Tugas berhasil dihapus.');
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Matrik Tugas');

        $headers = [
            'ID', 'ID Kegiatan', 'Nama Kegiatan', 'ID User', 'Nama Petugas', 'Tipe Petugas',
            'ID Pengawas', 'Nama Pengawas',
            'Role (JSON)', 'ID Wilayah', 'Nama Wilayah', 'Tanggal', 'Beban'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        $matriks = MatrikTugas::with(['kegiatan', 'user', 'pengawas', 'wilayah'])->get();
        foreach ($matriks as $m) {
            $sheet->setCellValue('A' . $row, $m->id);
            $sheet->setCellValue('B' . $row, $m->id_kegiatan);
            $sheet->setCellValue('C' . $row, $m->kegiatan?->nama);
            $sheet->setCellValue('D' . $row, $m->id_user);
            $sheet->setCellValue('E' . $row, $m->user?->name);
            $sheet->setCellValue('F' . $row, ucfirst($m->user?->tipe ?? ''));
            $sheet->setCellValue('G' . $row, $m->id_pengawas ?? '');
            $sheet->setCellValue('H' . $row, $m->pengawas?->name ?? '-');
            $sheet->setCellValue('I' . $row, json_encode($m->role ?? [], JSON_UNESCAPED_UNICODE));
            $sheet->setCellValue('J' . $row, $m->id_wilayah);
            $sheet->setCellValue('K' . $row, $m->wilayah?->nama);
            $sheet->setCellValue('L' . $row, $m->tgl ? $m->tgl->format('Y-m-d') : '');
            $sheet->setCellValue('M' . $row, $m->beban);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'matrik_tugas_export_' . date('Ymd_His') . '.xlsx';

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
            if (empty($r[1]) || empty($r[3])) continue;

            $roles = [];
            // Cek letak kolom role (kolom I index 8 atau kolom G index 6)
            $roleRaw = !empty($r[8]) ? $r[8] : (!empty($r[6]) ? $r[6] : '');
            if (!empty($roleRaw)) {
                $decoded = json_decode($roleRaw, true);
                if (is_array($decoded)) {
                    $roles = $decoded;
                } else {
                    $roles = array_map('trim', explode(',', (string)$roleRaw));
                }
            }

            $idPengawas = !empty($r[6]) && is_numeric($r[6]) ? (int)$r[6] : null;
            $idWilayah = !empty($r[9]) ? (int)$r[9] : (!empty($r[7]) ? (int)$r[7] : null);
            $tgl = !empty($r[11]) ? date('Y-m-d', strtotime($r[11])) : (!empty($r[9]) ? date('Y-m-d', strtotime($r[9])) : null);
            $beban = !empty($r[12]) ? (int)$r[12] : (!empty($r[10]) ? (int)$r[10] : 0);

            MatrikTugas::create([
                'id_kegiatan' => (int)$r[1],
                'id_user' => (int)$r[3],
                'id_pengawas' => $idPengawas,
                'role' => $roles,
                'id_wilayah' => $idWilayah,
                'tgl' => $tgl,
                'beban' => $beban,
            ]);
            $count++;
        }

        return redirect()->route('matrik.index')->with('success', "Berhasil mengimpor {$count} data matrik tugas dari Excel.");
    }
}
