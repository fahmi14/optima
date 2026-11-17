<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\PeriodeKegiatan;
use App\Models\SubKegiatan;
use App\Models\TimKerja;
use App\Models\User;
use App\Services\ExcelImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KegiatanController extends Controller
{
    public function __construct(
        private readonly ExcelImportExportService $excelService
    ) {}

    public function index(Request $request): View
    {
        $query = MasterKegiatan::with([
            'timKerja',
            'periodes' => function ($q) use ($request) {
                if ($request->filled('periode')) {
                    $q->where('nama_periode', $request->periode);
                }
                if ($request->filled('bulan')) {
                    $parts = explode('-', $request->bulan);
                    if (count($parts) === 2) {
                        $q->where(function ($sub) use ($parts) {
                            $sub->whereYear('tgl_mulai', (int) $parts[0])
                                ->whereMonth('tgl_mulai', (int) $parts[1]);
                        })->orWhere(function ($sub) use ($parts) {
                            $sub->whereYear('tgl_selesai', (int) $parts[0])
                                ->whereMonth('tgl_selesai', (int) $parts[1]);
                        });
                    }
                }
                $q->orderBy('tgl_mulai');
            },
            'periodes.subKegiatans.penugasans',
            'periodes.alokasiTransports',
        ]);

        if ($request->filled('tim_kerja_id')) {
            $query->where('tim_kerja_id', $request->tim_kerja_id);
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhereHas('periodes', fn ($pq) => $pq->where('nama_periode', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('periode') || $request->filled('bulan')) {
            $query->whereHas('periodes', function ($q) use ($request) {
                if ($request->filled('periode')) {
                    $q->where('nama_periode', $request->periode);
                }
                if ($request->filled('bulan')) {
                    $parts = explode('-', $request->bulan);
                    if (count($parts) === 2) {
                        $q->where(function ($sub) use ($parts) {
                            $sub->whereYear('tgl_mulai', (int) $parts[0])
                                ->whereMonth('tgl_mulai', (int) $parts[1]);
                        })->orWhere(function ($sub) use ($parts) {
                            $sub->whereYear('tgl_selesai', (int) $parts[0])
                                ->whereMonth('tgl_selesai', (int) $parts[1]);
                        });
                    }
                }
            });
        }

        $kegiatans = $query->orderByDesc('tahun')->orderBy('nama')->get();
        $timKerjas = TimKerja::orderBy('nama')->get();
        $tahunList = MasterKegiatan::select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        $periodeList = PeriodeKegiatan::select('nama_periode')->distinct()->orderBy('nama_periode')->pluck('nama_periode');

        return view('kegiatan.index', compact('kegiatans', 'timKerjas', 'tahunList', 'periodeList'));
    }

    public function storeMaster(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tim_kerja_id' => ['required', 'exists:tim_kerjas,id'],
            'nama' => ['required', 'string', 'max:255'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        MasterKegiatan::create($data);

        return back()->with('success', 'Master Kegiatan berhasil dibuat.');
    }

    public function destroyMaster(MasterKegiatan $master): RedirectResponse
    {
        $master->delete();

        return back()->with('success', 'Master Kegiatan berhasil dihapus.');
    }

    public function storePeriode(Request $request, MasterKegiatan $master): RedirectResponse
    {
        $data = $request->validate([
            'nama_periode' => ['required', 'string', 'max:255'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after_or_equal:tgl_mulai'],
            'pagu_transport' => ['required', 'numeric', 'min:0'],
        ]);

        $master->periodes()->create($data);

        return back()->with('success', 'Periode Kegiatan berhasil ditambahkan.');
    }

    public function destroyPeriode(PeriodeKegiatan $periode): RedirectResponse
    {
        $periode->delete();

        return redirect()->route('kegiatan.index')->with('success', 'Periode Kegiatan berhasil dihapus.');
    }

    public function showPeriode(PeriodeKegiatan $periode): View
    {
        $periode->load([
            'masterKegiatan.timKerja',
            'masterKegiatan.periodes',
            'subKegiatans.penugasans.user',
            'subKegiatans.penugasans.kecamatan',
            'subKegiatans.penugasans.alokasiTransports',
        ]);

        $otherPeriodes = PeriodeKegiatan::with('masterKegiatan')
            ->where('id', '!=', $periode->id)
            ->whereHas('subKegiatans')
            ->get();

        $petugasList = User::with('timKerja')
            ->whereIn('role', ['organik', 'mitra', 'katim'])
            ->orderBy('name')
            ->get();

        $kecamatans = Kecamatan::orderBy('nama')->get();

        $terpakaiTransport = $periode->totalTerpakaiTransport();
        $sisaPagu = $periode->sisaPaguTransport();

        return view('kegiatan.periode', compact(
            'periode',
            'otherPeriodes',
            'petugasList',
            'kecamatans',
            'terpakaiTransport',
            'sisaPagu'
        ));
    }

    public function storeSubKegiatan(Request $request, PeriodeKegiatan $periode): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_tahapan' => ['required', 'in:Teknis,Lapangan,Administrasi'],
            'deadline_tahapan' => ['nullable', 'date'],
            'estimasi_jam_per_satuan' => ['nullable', 'numeric', 'min:0.1'],
            'honor_per_satuan' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['urutan'] = $periode->subKegiatans()->count() + 1;
        $data['estimasi_jam_per_satuan'] = $data['estimasi_jam_per_satuan'] ?? 1.0;
        $data['honor_per_satuan'] = $data['honor_per_satuan'] ?? 0;

        $periode->subKegiatans()->create($data);

        return back()->with('success', 'Sub-Kegiatan / Tahapan berhasil ditambahkan.');
    }

    public function updateSubKegiatan(Request $request, SubKegiatan $sub): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_tahapan' => ['required', 'in:Teknis,Lapangan,Administrasi'],
            'deadline_tahapan' => ['nullable', 'date'],
            'estimasi_jam_per_satuan' => ['nullable', 'numeric', 'min:0.1'],
            'honor_per_satuan' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sub->update($data);

        return back()->with('success', 'Tahapan berhasil diperbarui.');
    }

    public function destroySubKegiatan(SubKegiatan $sub): RedirectResponse
    {
        $sub->delete();

        return back()->with('success', 'Tahapan berhasil dihapus.');
    }

    public function cloneSubKegiatan(Request $request, PeriodeKegiatan $periode): RedirectResponse
    {
        $request->validate([
            'source_periode_id' => ['required', 'exists:periode_kegiatans,id', 'different:' . $periode->id],
        ]);

        $source = PeriodeKegiatan::with('subKegiatans')->findOrFail($request->source_periode_id);
        if ($source->subKegiatans->isEmpty()) {
            return back()->with('error', 'Periode sumber belum memiliki tahapan.');
        }

        $urutan = $periode->subKegiatans()->count();
        $cloned = 0;

        foreach ($source->subKegiatans as $sub) {
            $urutan++;
            $periode->subKegiatans()->create([
                'nama' => $sub->nama,
                'jenis_tahapan' => $sub->jenis_tahapan,
                'deadline_tahapan' => $periode->tgl_selesai,
                'urutan' => $urutan,
            ]);
            $cloned++;
        }

        return back()->with('success', "{$cloned} tahapan berhasil disalin dari {$source->nama_periode}.");
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->excelService->downloadTemplateKegiatan();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:5120'],
        ]);

        $report = $this->excelService->importKegiatan($request->file('file'));

        return back()->with('import_report', [
            'title' => 'Laporan Import Kegiatan & Tahapan',
            'success_count' => $report['success_count'],
            'errors' => $report['errors'],
        ]);
    }
}
