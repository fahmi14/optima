<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organik;
use App\Models\Mitra;
use App\Models\Tim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['organik', 'mitra']);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('organik', function($oq) use ($search) {
                      $oq->where('nip', 'like', "%{$search}%")
                         ->orWhere('jabatan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('mitra', function($mq) use ($search) {
                      $mq->where('nik', 'like', "%{$search}%")
                         ->orWhere('no_hp', 'like', "%{$search}%")
                         ->orWhere('kecamatan', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->latest()->paginate(10);
        return view('user.index', compact('users'));
    }

    public function create()
    {
        $tims = Tim::all();
        return view('user.create', compact('tims'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'level' => 'required|in:admin,operator',
            'tipe' => 'required|in:organik,mitra',
        ];

        if ($request->tipe === 'organik') {
            $rules['nip'] = 'nullable|string|max:50';
            $rules['jabatan'] = 'nullable|string|max:255';
            $rules['golongan'] = 'nullable|string|max:50';
            $rules['id_tim'] = 'nullable|array';
        } else {
            $rules['nik'] = 'nullable|string|max:50';
            $rules['kecamatan'] = 'nullable|string|max:255';
            $rules['no_hp'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'level' => $validated['level'],
            'tipe' => $validated['tipe'],
        ]);

        if ($validated['tipe'] === 'organik') {
            Organik::create([
                'user_id' => $user->id,
                'nama' => $validated['name'],
                'nip' => $request->nip,
                'jabatan' => $request->jabatan,
                'golongan' => $request->golongan,
                'id_tim' => $request->id_tim ?? [],
            ]);
        } else {
            Mitra::create([
                'user_id' => $user->id,
                'nama' => $validated['name'],
                'nik' => $request->nik,
                'kecamatan' => $request->kecamatan,
                'no_hp' => $request->no_hp,
            ]);
        }

        return redirect()->route('user.index')->with('success', 'Data Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $user->load(['organik', 'mitra']);
        $tims = Tim::all();
        return view('user.edit', compact('user', 'tims'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'level' => 'required|in:admin,operator',
            'tipe' => 'required|in:organik,mitra',
        ];

        if ($request->tipe === 'organik') {
            $rules['nip'] = 'nullable|string|max:50';
            $rules['jabatan'] = 'nullable|string|max:255';
            $rules['golongan'] = 'nullable|string|max:50';
            $rules['id_tim'] = 'nullable|array';
        } else {
            $rules['nik'] = 'nullable|string|max:50';
            $rules['kecamatan'] = 'nullable|string|max:255';
            $rules['no_hp'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'level' => $validated['level'],
            'tipe' => $validated['tipe'],
        ];
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }
        $user->update($userData);

        if ($validated['tipe'] === 'organik') {
            $user->mitra()?->delete();
            Organik::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama' => $validated['name'],
                    'nip' => $request->nip,
                    'jabatan' => $request->jabatan,
                    'golongan' => $request->golongan,
                    'id_tim' => $request->id_tim ?? [],
                ]
            );
        } else {
            $user->organik()?->delete();
            Mitra::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama' => $validated['name'],
                    'nik' => $request->nik,
                    'kecamatan' => $request->kecamatan,
                    'no_hp' => $request->no_hp,
                ]
            );
        }

        return redirect()->route('user.index')->with('success', 'Data Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('user.index')->with('success', 'Data Pengguna berhasil dihapus.');
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengguna');

        $headers = [
            'ID', 'Nama', 'Email', 'Level', 'Tipe',
            'NIP', 'Jabatan', 'Golongan', 'ID Tim (Organik)',
            'NIK', 'Kecamatan (Mitra)', 'No HP'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        $users = User::with(['organik', 'mitra'])->get();
        foreach ($users as $u) {
            $sheet->setCellValue('A' . $row, $u->id);
            $sheet->setCellValue('B' . $row, $u->name);
            $sheet->setCellValue('C' . $row, $u->email);
            $sheet->setCellValue('D' . $row, $u->level);
            $sheet->setCellValue('E' . $row, $u->tipe);

            if ($u->tipe === 'organik' && $u->organik) {
                $sheet->setCellValue('F' . $row, $u->organik->nip);
                $sheet->setCellValue('G' . $row, $u->organik->jabatan);
                $sheet->setCellValue('H' . $row, $u->organik->golongan);
                $sheet->setCellValue('I' . $row, is_array($u->organik->id_tim) ? implode(',', $u->organik->id_tim) : $u->organik->id_tim);
            } else {
                $sheet->setCellValue('F' . $row, '-');
                $sheet->setCellValue('G' . $row, '-');
                $sheet->setCellValue('H' . $row, '-');
                $sheet->setCellValue('I' . $row, '-');
            }

            if ($u->tipe === 'mitra' && $u->mitra) {
                $sheet->setCellValue('J' . $row, $u->mitra->nik);
                $sheet->setCellValue('K' . $row, $u->mitra->kecamatan);
                $sheet->setCellValue('L' . $row, $u->mitra->no_hp);
            } else {
                $sheet->setCellValue('J' . $row, '-');
                $sheet->setCellValue('K' . $row, '-');
                $sheet->setCellValue('L' . $row, '-');
            }

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'pengguna_export_' . date('Ymd_His') . '.xlsx';

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
            if (empty($r[1]) || empty($r[2])) continue;

            $email = trim($r[2]);
            $tipe = in_array(strtolower(trim($r[4] ?? '')), ['mitra', 'organik']) ? strtolower(trim($r[4])) : 'mitra';
            $level = in_array(strtolower(trim($r[3] ?? '')), ['admin', 'operator']) ? strtolower(trim($r[3])) : 'operator';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => trim($r[1]),
                    'password' => Hash::make('password123'),
                    'level' => $level,
                    'tipe' => $tipe,
                ]
            );

            if ($tipe === 'organik') {
                $timIds = !empty($r[8]) ? array_map('intval', explode(',', (string)$r[8])) : [];
                Organik::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama' => $user->name,
                        'nip' => $r[5] ?? null,
                        'jabatan' => $r[6] ?? null,
                        'golongan' => $r[7] ?? null,
                        'id_tim' => $timIds,
                    ]
                );
            } else {
                Mitra::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama' => $user->name,
                        'nik' => $r[9] ?? null,
                        'kecamatan' => $r[10] ?? null,
                        'no_hp' => $r[11] ?? null,
                    ]
                );
            }
            $count++;
        }

        return redirect()->route('user.index')->with('success', "Berhasil mengimpor {$count} data pengguna dari Excel.");
    }
}
