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

class UserController extends Controller
{
    public function __construct(
        private readonly ExcelImportExportService $excelService,
        private readonly WorkloadService $workloadService
    ) {}

    public function index(Request $request): View
    {
        $query = User::with(['timKerja', 'kecamatan', 'penugasans' => function ($q) {
            $q->where('status', '!=', 'Selesai');
        }]);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

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
                    ->orWhere('id_sobat', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $workloads = [];
        foreach ($users as $u) {
            $workloads[$u->id] = $this->workloadService->calculateUserWorkload($u);
        }

        $timKerjas = TimKerja::orderBy('nama')->get();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('user.index', compact('users', 'workloads', 'timKerjas', 'kecamatans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4'],
            'role' => ['required', 'in:admin,pimpinan,katim,keuangan,organik,mitra'],
            'tipe' => ['required', 'in:organik,mitra'],
            'nip' => ['nullable', 'string', 'max:30'],
            'id_sobat' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tim_kerja_id' => ['nullable', 'exists:tim_kerjas,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['kapasitas_maks'] = $data['kapasitas_maks'] ?? 100;

        User::create($data);

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', 'in:admin,pimpinan,katim,keuangan,organik,mitra'],
            'tipe' => ['required', 'in:organik,mitra'],
            'nip' => ['nullable', 'string', 'max:30'],
            'id_sobat' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'kapasitas_maks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tim_kerja_id' => ['nullable', 'exists:tim_kerjas,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatans,id'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('user.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function downloadTemplateOrganik(): StreamedResponse
    {
        return $this->excelService->downloadTemplateOrganik();
    }

    public function downloadTemplateMitra(): StreamedResponse
    {
        return $this->excelService->downloadTemplateMitra();
    }

    public function importOrganik(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:5120'],
        ]);

        $report = $this->excelService->importOrganik($request->file('file'));

        return back()->with('import_report', [
            'title' => 'Laporan Import Pegawai Organik',
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }

    public function importMitra(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:5120'],
        ]);

        $report = $this->excelService->importMitra($request->file('file'));

        return back()->with('import_report', [
            'title' => 'Laporan Import Mitra Statistik',
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }
}
