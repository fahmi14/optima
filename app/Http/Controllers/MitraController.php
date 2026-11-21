<?php

namespace App\Http\Controllers;

use App\Models\AlokasiKepka;
use App\Models\Kecamatan;
use App\Models\MasterMitra;
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
        $tahunList = AlokasiKepka::select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        $selectedTahun = $request->filled('tahun')
            ? (int) $request->tahun
            : ($tahunList->first() ?? (int) date('Y'));

        $query = AlokasiKepka::with(['masterMitra.user.kecamatan', 'masterMitra.user.penugasans' => function ($q) {
            $q->where('status', '!=', 'Selesai');
        }])->where('tahun', $selectedTahun);

        if ($request->filled('kecamatan')) {
            $kecFilter = $request->kecamatan;
            $query->whereHas('masterMitra', function ($q) use ($kecFilter) {
                $q->where('alamat_kecamatan', 'like', "%{$kecFilter}%")
                    ->orWhereHas('user.kecamatan', fn ($kq) => $kq->where('nama', $kecFilter));
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('masterMitra', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('sobat_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%")
                    ->orWhere('pekerjaan', 'like', "%{$search}%")
                    ->orWhere('alamat_detail', 'like', "%{$search}%");
            });
        }

        $mitras = $query->join('master_mitras', 'alokasi_kepkas.id_mitra', '=', 'master_mitras.id')
            ->select('alokasi_kepkas.*')
            ->orderBy('master_mitras.nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        $workloads = [];
        $mitras->getCollection()->transform(function (AlokasiKepka $alokasi) use (&$workloads) {
            $mm = $alokasi->masterMitra;
            $user = $mm?->user;

            if ($user) {
                $workloads[$user->id] = $this->workloadService->calculateUserWorkload($user);
            }

            // Gabungkan atribut MasterMitra ke object AlokasiKepka agar view tetap konsisten
            $alokasi->setAttribute('user_id', $mm?->user_id);
            $alokasi->setRelation('user', $user);
            foreach ([
                'sobat_id', 'nama_lengkap', 'posisi', 'status_seleksi', 'alamat_detail',
                'alamat_provinsi', 'alamat_kab', 'alamat_kecamatan', 'alamat_desa',
                'ttl', 'umur', 'jenis_kelamin', 'status_kawin', 'pendidikan',
                'pekerjaan', 'deskripsi_pekerjaan_lain', 'terikat_kldi',
                'nama_kldi', 'keterangan_kldi', 'no_telp', 'email',
            ] as $field) {
                $alokasi->setAttribute($field, $mm?->{$field});
            }

            return $alokasi;
        });

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
            $sobatId = trim((string) ($data['sobat_id'] ?? ''));
            $user = $sobatId !== ''
                ? User::where('id_sobat', $sobatId)->orWhere('email', $data['email'])->first()
                : User::where('email', $data['email'])->first();

            if (!$user) {
                $user = new User(['email' => $data['email']]);
                $user->password = Hash::make('bps3307');
                $user->role = 'mitra';
                $user->tipe = 'mitra';
            }

            $user->name = $data['nama_lengkap'];
            $user->id_sobat = $sobatId !== '' ? $sobatId : $user->id_sobat;
            $user->no_hp = $data['no_telp'] ?? $user->no_hp;
            $user->alamat = $data['alamat_detail'] ?? $user->alamat;
            $user->kecamatan_id = $kec?->id ?? $user->kecamatan_id;
            $user->kapasitas_maks = $data['kapasitas_maks'] ?? 100;
            $user->save();

            $mitraPayload = [
                'user_id' => $user->id,
                'sobat_id' => $sobatId !== '' ? $sobatId : null,
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
                'email' => $data['email'],
            ];

            // Jika sobat_id sama, cukup pakai 1 record master_mitras
            $masterMitra = $sobatId !== ''
                ? MasterMitra::updateOrCreate(['sobat_id' => $sobatId], $mitraPayload)
                : MasterMitra::updateOrCreate(['user_id' => $user->id], $mitraPayload);

            AlokasiKepka::firstOrCreate([
                'id_mitra' => $masterMitra->id,
                'tahun' => $data['tahun'],
            ]);
        });

        return redirect()->route('mitra.index', ['tahun' => $data['tahun']])
            ->with('success', 'Data Mitra KEPKA berhasil disimpan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $alokasi = AlokasiKepka::with('masterMitra.user')->findOrFail($id);
        $masterMitra = $alokasi->masterMitra;

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

        DB::transaction(function () use ($masterMitra, $data, $kec) {
            if ($masterMitra) {
                $masterMitra->update([
                    'nama_lengkap' => $data['nama_lengkap'],
                    'sobat_id' => $data['sobat_id'] ?? $masterMitra->sobat_id,
                    'email' => $data['email'] ?? $masterMitra->email,
                    'no_telp' => $data['no_telp'] ?? $masterMitra->no_telp,
                    'alamat_kecamatan' => $kec?->nama ?? $masterMitra->alamat_kecamatan,
                    'alamat_detail' => $data['alamat_detail'] ?? $masterMitra->alamat_detail,
                    'pekerjaan' => $data['pekerjaan'] ?? $masterMitra->pekerjaan,
                    'pendidikan' => $data['pendidikan'] ?? $masterMitra->pendidikan,
                    'terikat_kldi' => $data['terikat_kldi'] ?? $masterMitra->terikat_kldi,
                    'nama_kldi' => $data['nama_kldi'] ?? $masterMitra->nama_kldi,
                ]);

                if ($masterMitra->user) {
                    $masterMitra->user->update([
                        'name' => $data['nama_lengkap'],
                        'id_sobat' => $data['sobat_id'] ?? $masterMitra->user->id_sobat,
                        'no_hp' => $data['no_telp'] ?? $masterMitra->user->no_hp,
                        'alamat' => $data['alamat_detail'] ?? $masterMitra->user->alamat,
                        'kecamatan_id' => $kec?->id ?? $masterMitra->user->kecamatan_id,
                        'kapasitas_maks' => $data['kapasitas_maks'] ?? $masterMitra->user->kapasitas_maks,
                    ]);
                }
            }
        });

        return back()->with('success', 'Data Mitra KEPKA berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        AlokasiKepka::findOrFail($id)->delete();

        return back()->with('success', 'Mitra berhasil dihapus dari alokasi KEPKA tahun tersebut.');
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
