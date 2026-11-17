<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\MitraKepka;
use App\Models\User;
use App\Services\ExcelImportExportService;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MitraController extends Controller
{
    public function __construct(
        private readonly ExcelImportExportService $excelService,
        private readonly WorkloadService $workloadService
    ) {}

    public function index(Request $request): View
    {
        $tahunList = MitraKepka::select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        $selectedTahun = $request->filled('tahun')
            ? (int) $request->tahun
            : ($tahunList->first() ?? (int) date('Y'));

        $query = MitraKepka::with(['user.kecamatan', 'user.penugasans' => function ($q) {
            $q->where('status', '!=', 'Selesai');
        }])->where('tahun', $selectedTahun);

        if ($request->filled('kecamatan')) {
            $kecFilter = $request->kecamatan;
            $query->where(function ($q) use ($kecFilter) {
                $q->where('alamat_kecamatan', 'like', "%{$kecFilter}%")
                    ->orWhereHas('user.kecamatan', fn ($kq) => $kq->where('nama', $kecFilter));
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('sobat_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%")
                    ->orWhere('pekerjaan', 'like', "%{$search}%")
                    ->orWhere('alamat_detail', 'like', "%{$search}%");
            });
        }

        $mitras = $query->orderBy('nama_lengkap')->paginate(20)->withQueryString();

        $workloads = [];
        foreach ($mitras as $m) {
            if ($m->user) {
                $workloads[$m->user_id] = $this->workloadService->calculateUserWorkload($m->user);
            }
        }

        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('mitra.index', compact(
            'mitras',
            'workloads',
            'tahunList',
            'selectedTahun',
            'kecamatans'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'sobat_id' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email'],
            'no_telp' => ['nullable', 'string', 'max:50'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
            'alamat_detail' => ['nullable', 'string'],
            'alamat_desa' => ['nullable', 'string', 'max:255'],
            'ttl' => ['nullable', 'string', 'max:255'],
            'umur' => ['nullable', 'integer', 'min:15', 'max:99'],
            'jenis_kelamin' => ['nullable', 'string', 'max:20'],
            'status_kawin' => ['nullable', 'string', 'max:50'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'terikat_kldi' => ['nullable', 'string', 'max:20'],
            'nama_kldi' => ['nullable', 'string', 'max:255'],
            'keterangan_kldi' => ['nullable', 'string'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $kec = !empty($data['kecamatan_id']) ? Kecamatan::find($data['kecamatan_id']) : null;

        DB::transaction(function () use ($data, $kec) {
            $user = User::firstOrNew(['email' => $data['email']]);
            if (!$user->exists) {
                $user->password = Hash::make('bps3307');
                $user->role = 'mitra';
                $user->tipe = 'mitra';
            }
            $user->name = $data['nama_lengkap'];
            $user->id_sobat = $data['sobat_id'] ?? $user->id_sobat;
            $user->no_hp = $data['no_telp'] ?? $user->no_hp;
            $user->alamat = $data['alamat_detail'] ?? $user->alamat;
            $user->kecamatan_id = $kec?->id ?? $user->kecamatan_id;
            $user->kapasitas_maks = $data['kapasitas_maks'] ?? 100;
            $user->save();

            MitraKepka::updateOrCreate(
                ['user_id' => $user->id, 'tahun' => $data['tahun']],
                [
                    'nama_lengkap' => $data['nama_lengkap'],
                    'alamat_detail' => $data['alamat_detail'] ?? null,
                    'alamat_provinsi' => '(33) JAWA TENGAH',
                    'alamat_kab' => '(07) WONOSOBO',
                    'alamat_kecamatan' => $kec?->nama,
                    'alamat_desa' => $data['alamat_desa'] ?? null,
                    'ttl' => $data['ttl'] ?? null,
                    'umur' => $data['umur'] ?? null,
                    'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                    'status_kawin' => $data['status_kawin'] ?? null,
                    'pendidikan' => $data['pendidikan'] ?? null,
                    'pekerjaan' => $data['pekerjaan'] ?? null,
                    'terikat_kldi' => $data['terikat_kldi'] ?? null,
                    'nama_kldi' => $data['nama_kldi'] ?? null,
                    'keterangan_kldi' => $data['keterangan_kldi'] ?? null,
                    'no_telp' => $data['no_telp'] ?? null,
                    'sobat_id' => $data['sobat_id'] ?? null,
                    'email' => $data['email'],
                ]
            );
        });

        return redirect()->route('mitra.index', ['tahun' => $data['tahun']])
            ->with('success', 'Data Mitra KEPKA berhasil disimpan.');
    }

    public function update(Request $request, MitraKepka $mitra): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'sobat_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'no_telp' => ['nullable', 'string', 'max:50'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
            'alamat_detail' => ['nullable', 'string'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'terikat_kldi' => ['nullable', 'string', 'max:20'],
            'nama_kldi' => ['nullable', 'string', 'max:255'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $kec = !empty($data['kecamatan_id']) ? Kecamatan::find($data['kecamatan_id']) : null;

        DB::transaction(function () use ($mitra, $data, $kec) {
            $mitra->update([
                'nama_lengkap' => $data['nama_lengkap'],
                'sobat_id' => $data['sobat_id'] ?? $mitra->sobat_id,
                'email' => $data['email'] ?? $mitra->email,
                'no_telp' => $data['no_telp'] ?? $mitra->no_telp,
                'alamat_kecamatan' => $kec?->nama ?? $mitra->alamat_kecamatan,
                'alamat_detail' => $data['alamat_detail'] ?? $mitra->alamat_detail,
                'pekerjaan' => $data['pekerjaan'] ?? $mitra->pekerjaan,
                'pendidikan' => $data['pendidikan'] ?? $mitra->pendidikan,
                'terikat_kldi' => $data['terikat_kldi'] ?? $mitra->terikat_kldi,
                'nama_kldi' => $data['nama_kldi'] ?? $mitra->nama_kldi,
            ]);

            if ($mitra->user) {
                $mitra->user->update([
                    'name' => $data['nama_lengkap'],
                    'id_sobat' => $data['sobat_id'] ?? $mitra->user->id_sobat,
                    'no_hp' => $data['no_telp'] ?? $mitra->user->no_hp,
                    'alamat' => $data['alamat_detail'] ?? $mitra->user->alamat,
                    'kecamatan_id' => $kec?->id ?? $mitra->user->kecamatan_id,
                    'kapasitas_maks' => $data['kapasitas_maks'] ?? $mitra->user->kapasitas_maks,
                ]);
            }
        });

        return back()->with('success', 'Data Mitra KEPKA berhasil diperbarui.');
    }

    public function destroy(MitraKepka $mitra): RedirectResponse
    {
        $mitra->delete();

        return back()->with('success', 'Data Mitra KEPKA tahun tersebut berhasil dihapus.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->excelService->downloadTemplateMitra();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:20480'],
        ]);

        $tahun = (int) $request->tahun;
        $report = $this->excelService->importMitra($request->file('file'), $tahun);

        return redirect()->route('mitra.index', ['tahun' => $tahun])->with('import_report', [
            'title' => "Laporan Import Mitra KEPKA Tahun {$tahun}",
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }
}
