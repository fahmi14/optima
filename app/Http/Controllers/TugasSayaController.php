<?php

namespace App\Http\Controllers;

use App\Models\Penugasan;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TugasSayaController extends Controller
{
    public function __construct(
        private readonly WorkloadService $workloadService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Penugasan::with([
            'subKegiatan.periodeKegiatan.masterKegiatan.timKerja',
            'kecamatan',
            'alokasiTransports',
        ])->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $penugasans = $query->orderByRaw("FIELD(status, 'Revisi', 'Berjalan', 'Belum Mulai', 'Menunggu Verifikasi', 'Selesai')")
            ->orderBy('deadline_tugas')
            ->get();

        $workload = $this->workloadService->calculateUserWorkload($user);
        $totalHakTransport = (float) $user->alokasiTransports()
            ->where('status_spj', '!=', 'Ditolak')
            ->sum('nominal');

        return view('tugas-saya.index', compact('penugasans', 'workload', 'totalHakTransport'));
    }

    public function updateProgress(Request $request, Penugasan $penugasan): RedirectResponse
    {
        if ($penugasan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mengubah tugas ini.');
        }

        $data = $request->validate([
            'realisasi_volume' => ['required', 'integer', 'min:0'],
            'progres_persen' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:Belum Mulai,Berjalan,Menunggu Verifikasi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'file_bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $filePath = $penugasan->file_bukti;
        if ($request->hasFile('file_bukti')) {
            $uploadDir = public_path('uploads/bukti');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->file('file_bukti');
            $filename = 'bukti_' . $penugasan->id . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $filePath = 'uploads/bukti/' . $filename;
        }

        $penugasan->update([
            'realisasi_volume' => $data['realisasi_volume'],
            'progres_persen' => $data['progres_persen'],
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? $penugasan->catatan,
            'file_bukti' => $filePath,
        ]);

        return back()->with('success', 'Progres tugas dan bukti pelaksanaan berhasil disimpan.');
    }
}
