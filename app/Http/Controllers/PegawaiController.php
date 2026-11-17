<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\TimKerja;
use App\Models\User;
use App\Services\ExcelImportExportService;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PegawaiController extends Controller
{
    public function __construct(
        private readonly ExcelImportExportService $excelService,
        private readonly WorkloadService $workloadService
    ) {}

    public function index(Request $request): View
    {
        $query = User::with(['timKerja', 'kecamatan', 'penugasans' => function ($q) {
            $q->where('status', '!=', 'Selesai');
        }])->where('tipe', 'organik');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('tim_kerja_id')) {
            $query->where('tim_kerja_id', $request->tim_kerja_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%")
                    ->orWhere('pangkat_golongan', 'like', "%{$search}%");
            });
        }

        $pegawais = $query->orderBy('name')->paginate(15)->withQueryString();
        $workloads = [];
        foreach ($pegawais as $p) {
            $workloads[$p->id] = $this->workloadService->calculateUserWorkload($p);
        }

        $timKerjas = TimKerja::orderBy('nama')->get();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('pegawai.index', compact('pegawais', 'workloads', 'timKerjas', 'kecamatans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4'],
            'role' => ['required', 'in:admin,pimpinan,katim,keuangan,organik'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tim_kerja_id' => ['nullable', 'exists:tim_kerjas,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
        ]);

        $data['tipe'] = 'organik';
        $data['password'] = Hash::make($data['password']);
        $data['kapasitas_maks'] = $data['kapasitas_maks'] ?? 100;

        User::create($data);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function update(Request $request, User $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username,' . $pegawai->id],
            'email' => ['required', 'email', 'unique:users,email,' . $pegawai->id],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', 'in:admin,pimpinan,katim,keuangan,organik'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tim_kerja_id' => ['nullable', 'exists:tim_kerjas,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $pegawai->update($data);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $pegawai): RedirectResponse
    {
        if ($pegawai->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->excelService->downloadTemplateOrganik();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:10240'],
        ]);

        $report = $this->excelService->importOrganik($request->file('file'));

        return back()->with('import_report', [
            'title' => 'Laporan Import Pegawai Organik',
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }
}
