<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Nks;
use App\Models\Sls;
use App\Models\SubSls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterWilayahController extends Controller
{
    /**
     * Halaman Utama Master Wilayah (5 Tab: Kecamatan, Desa, SLS, Sub-SLS, NKS)
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'kecamatan');
        $search = $request->query('search', '');

        $kecamatans = Kecamatan::orderBy('nama')->get();

        $data = match ($tab) {
            'desa' => Desa::with('kecamatan')
                ->when($search, function ($q) use ($search) {
                    $q->where('desa', 'like', "%{$search}%")
                      ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$search}%"));
                })
                ->orderBy('id_kec')
                ->orderBy('desa')
                ->paginate(15)
                ->withQueryString(),

            'sls' => Sls::with(['kecamatan', 'desa'])
                ->when($search, function ($q) use ($search) {
                    $q->where('sls', 'like', "%{$search}%")
                      ->orWhere('id_sls', 'like', "%{$search}%")
                      ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$search}%"))
                      ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$search}%"));
                })
                ->orderBy('id_kec')
                ->orderBy('id_desa')
                ->paginate(15)
                ->withQueryString(),

            'sub_sls' => SubSls::with(['kecamatan', 'desa'])
                ->when($search, function ($q) use ($search) {
                    $q->where('subsls', 'like', "%{$search}%")
                      ->orWhere('id_sls', 'like', "%{$search}%")
                      ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$search}%"))
                      ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$search}%"));
                })
                ->orderBy('id_kec')
                ->orderBy('id_desa')
                ->paginate(15)
                ->withQueryString(),

            'nks' => Nks::with(['kecamatan', 'desa'])
                ->when($search, function ($q) use ($search) {
                    $q->where('nks', 'like', "%{$search}%")
                      ->orWhere('sls', 'like', "%{$search}%")
                      ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$search}%"))
                      ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$search}%"));
                })
                ->orderBy('id_kec')
                ->orderBy('id_desa')
                ->paginate(15)
                ->withQueryString(),

            default => Kecamatan::withCount('desas')
                ->when($search, function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                })
                ->orderBy('kode')
                ->paginate(15)
                ->withQueryString(),
        };

        // Ambil semua desa untuk dropdown modal tambah/edit
        $desasList = Desa::orderBy('desa')->get(['id', 'id_kec', 'desa']);

        return view('wilayah.master', compact('tab', 'search', 'data', 'kecamatans', 'desasList'));
    }

    /**
     * Store Data Wilayah (Berdasarkan Tab)
     */
    public function store(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'kecamatan');

        if ($tab === 'kecamatan') {
            $data = $request->validate([
                'kode' => ['required', 'string', 'max:20', 'unique:kecamatans,kode'],
                'nama' => ['required', 'string', 'max:100'],
            ]);
            Kecamatan::create($data);
        } elseif ($tab === 'desa') {
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'desa' => ['required', 'string', 'max:100'],
                'kd_desa' => ['nullable', 'string', 'max:20'],
            ]);
            Desa::create($data);
        } elseif ($tab === 'sls') {
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'sls' => ['required', 'string', 'max:150'],
            ]);
            Sls::create($data);
        } elseif ($tab === 'sub_sls') {
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'subsls' => ['required', 'string', 'max:150'],
            ]);
            SubSls::create($data);
        } elseif ($tab === 'nks') {
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'sls' => ['nullable', 'string', 'max:150'],
                'nks' => ['required', 'string', 'max:50'],
            ]);
            Nks::create($data);
        }

        return redirect()->route('master.wilayah.index', ['tab' => $tab])
            ->with('success', 'Data wilayah berhasil ditambahkan.');
    }

    /**
     * Update Data Wilayah (Berdasarkan Tab)
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $tab = $request->input('tab', 'kecamatan');

        if ($tab === 'kecamatan') {
            $kec = Kecamatan::findOrFail($id);
            $data = $request->validate([
                'kode' => ['required', 'string', 'max:20', 'unique:kecamatans,kode,' . $id],
                'nama' => ['required', 'string', 'max:100'],
            ]);
            $kec->update($data);
        } elseif ($tab === 'desa') {
            $desa = Desa::findOrFail($id);
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'desa' => ['required', 'string', 'max:100'],
                'kd_desa' => ['nullable', 'string', 'max:20'],
            ]);
            $desa->update($data);
        } elseif ($tab === 'sls') {
            $sls = Sls::findOrFail($id);
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'sls' => ['required', 'string', 'max:150'],
            ]);
            $sls->update($data);
        } elseif ($tab === 'sub_sls') {
            $subsls = SubSls::findOrFail($id);
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'subsls' => ['required', 'string', 'max:150'],
            ]);
            $subsls->update($data);
        } elseif ($tab === 'nks') {
            $nks = Nks::findOrFail($id);
            $data = $request->validate([
                'id_kec' => ['required', 'exists:kecamatans,id'],
                'id_desa' => ['required', 'exists:desas,id'],
                'id_sls' => ['nullable', 'string', 'max:50'],
                'sls' => ['nullable', 'string', 'max:150'],
                'nks' => ['required', 'string', 'max:50'],
            ]);
            $nks->update($data);
        }

        return redirect()->route('master.wilayah.index', ['tab' => $tab])
            ->with('success', 'Data wilayah berhasil diperbarui.');
    }

    /**
     * Delete Data Wilayah (Berdasarkan Tab)
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $tab = $request->input('tab', 'kecamatan');

        if ($tab === 'kecamatan') {
            Kecamatan::findOrFail($id)->delete();
        } elseif ($tab === 'desa') {
            Desa::findOrFail($id)->delete();
        } elseif ($tab === 'sls') {
            Sls::findOrFail($id)->delete();
        } elseif ($tab === 'sub_sls') {
            SubSls::findOrFail($id)->delete();
        } elseif ($tab === 'nks') {
            Nks::findOrFail($id)->delete();
        }

        return redirect()->route('master.wilayah.index', ['tab' => $tab])
            ->with('success', 'Data wilayah berhasil dihapus.');
    }

    /**
     * Download Template Excel untuk Masing-masing Level Wilayah
     */
    public function downloadTemplate(string $tab): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($tab === 'kecamatan') {
            $sheet->setTitle('Template Kecamatan');
            $sheet->fromArray([
                ['kode', 'nama'],
                ['330701', 'Wadaslintang'],
                ['330702', 'Kepil'],
            ]);
        } elseif ($tab === 'desa') {
            $sheet->setTitle('Template Desa');
            $sheet->fromArray([
                ['id_kec', 'kecamatan', 'kd_desa', 'desa'],
                ['1', 'Wadaslintang', '3307010001', 'Kumejing'],
                ['1', 'Wadaslintang', '3307010002', 'Lancar'],
            ]);
        } elseif ($tab === 'sls') {
            $sheet->setTitle('Template SLS');
            $sheet->fromArray([
                ['id_kec', 'id_desa', 'id_sls', 'sls'],
                ['1', '1', '0001', 'RT 001 RW 001'],
                ['1', '1', '0002', 'RT 002 RW 001'],
            ]);
        } elseif ($tab === 'sub_sls') {
            $sheet->setTitle('Template Sub-SLS');
            $sheet->fromArray([
                ['id_kec', 'id_desa', 'id_sls', 'subsls'],
                ['1', '1', '0001', 'Sub-SLS 01'],
                ['1', '1', '0001', 'Sub-SLS 02'],
            ]);
        } elseif ($tab === 'nks') {
            $sheet->setTitle('Template NKS');
            $sheet->fromArray([
                ['id_kec', 'id_desa', 'id_sls', 'sls', 'nks'],
                ['1', '1', '0001', 'RT 001 RW 001', '3307010001'],
                ['1', '1', '0002', 'RT 002 RW 001', '3307010002'],
            ]);
        }

        foreach (range('A', 'E') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "template_wilayah_{$tab}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import Data Wilayah dari File Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'tab' => ['required', 'string', 'in:kecamatan,desa,sls,sub_sls,nks'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:15360'],
        ]);

        $tab = $request->input('tab');
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $count = 0;
        $kecamatansMap = Kecamatan::all()->keyBy('id');
        $desasMap = Desa::all()->keyBy('id');

        // Lewati row header (index 0)
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            if (empty(array_filter($r))) continue;

            if ($tab === 'kecamatan') {
                // [kode, nama]
                $kode = trim((string) ($r[0] ?? ''));
                $nama = trim((string) ($r[1] ?? ''));
                if (!$kode || !$nama) continue;
                Kecamatan::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
                $count++;
            } elseif ($tab === 'desa') {
                // [id_kec, kecamatan (opsional), kd_desa, desa]
                $idKec = (int) ($r[0] ?? 0);
                if (!$idKec && !empty($r[1])) {
                    // Cari id_kec berdasarkan nama
                    $kecObj = Kecamatan::where('nama', 'like', '%' . trim((string) $r[1]) . '%')->first();
                    $idKec = $kecObj?->id;
                }
                $kdDesa = trim((string) ($r[2] ?? ''));
                $namaDesa = trim((string) ($r[3] ?? ''));
                if (!$idKec || !$namaDesa) continue;

                Desa::create([
                    'id_kec' => $idKec,
                    'kd_desa' => $kdDesa,
                    'desa' => $namaDesa,
                ]);
                $count++;
            } elseif ($tab === 'sls') {
                // [id_kec, id_desa, id_sls, sls]
                $idKec = (int) ($r[0] ?? 0);
                $idDesa = (int) ($r[1] ?? 0);
                $idSls = trim((string) ($r[2] ?? ''));
                $namaSls = trim((string) ($r[3] ?? ''));
                if (!$idKec || !$idDesa || !$namaSls) continue;

                Sls::create([
                    'id_kec' => $idKec,
                    'id_desa' => $idDesa,
                    'id_sls' => $idSls,
                    'sls' => $namaSls,
                ]);
                $count++;
            } elseif ($tab === 'sub_sls') {
                // [id_kec, id_desa, id_sls, subsls]
                $idKec = (int) ($r[0] ?? 0);
                $idDesa = (int) ($r[1] ?? 0);
                $idSls = trim((string) ($r[2] ?? ''));
                $subsls = trim((string) ($r[3] ?? ''));
                if (!$idKec || !$idDesa || !$subsls) continue;

                SubSls::create([
                    'id_kec' => $idKec,
                    'id_desa' => $idDesa,
                    'id_sls' => $idSls,
                    'subsls' => $subsls,
                ]);
                $count++;
            } elseif ($tab === 'nks') {
                // [id_kec, id_desa, id_sls, sls, nks]
                $idKec = (int) ($r[0] ?? 0);
                $idDesa = (int) ($r[1] ?? 0);
                $idSls = trim((string) ($r[2] ?? ''));
                $namaSls = trim((string) ($r[3] ?? ''));
                $nks = trim((string) ($r[4] ?? ''));
                if (!$idKec || !$idDesa || !$nks) continue;

                Nks::create([
                    'id_kec' => $idKec,
                    'id_desa' => $idDesa,
                    'id_sls' => $idSls,
                    'sls' => $namaSls,
                    'nks' => $nks,
                ]);
                $count++;
            }
        }

        return redirect()->route('master.wilayah.index', ['tab' => $tab])
            ->with('success', "Berhasil mengimpor {$count} data pada level " . strtoupper($tab) . ".");
    }

    /**
     * Endpoint API Autocomplete Wilayah Tugas (Kecamatan, Desa, SLS, Sub-SLS, NKS)
     * Format output berjenjang agar petugas tidak salah pilih:
     * e.g. "RT 001 RW 003 (Kec Kertek, Desa Bijiarum)"
     */
    public function search(Request $request): JsonResponse
    {
        $level = $request->query('level', 'kecamatan');
        $q = trim((string) $request->query('q', ''));
        $kecamatanId = $request->query('kecamatan_id');

        $results = [];

        if ($level === 'kecamatan') {
            $kecamatans = Kecamatan::query()
                ->when($q, fn($query) => $query->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%"))
                ->limit(20)
                ->get();

            foreach ($kecamatans as $k) {
                $results[] = [
                    'label' => "Kec. {$k->nama} [{$k->kode}]",
                    'value' => "Kec. {$k->nama}",
                    'id' => $k->id,
                    'subtext' => "Kabupaten Wonosobo",
                ];
            }
        } elseif ($level === 'desa') {
            $desas = Desa::with('kecamatan')
                ->when($kecamatanId, fn($query) => $query->where('id_kec', $kecamatanId))
                ->when($q, function ($query) use ($q) {
                    $query->where('desa', 'like', "%{$q}%")
                          ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$q}%"));
                })
                ->limit(25)
                ->get();

            foreach ($desas as $d) {
                $kecNama = $d->kecamatan?->nama ?? '-';
                $results[] = [
                    'label' => "{$d->desa} (Kec {$kecNama})",
                    'value' => "{$d->desa} (Kec {$kecNama})",
                    'id' => $d->id,
                    'id_kec' => $d->id_kec,
                    'subtext' => "Kecamatan {$kecNama}",
                ];
            }
        } elseif ($level === 'sls') {
            $slsList = Sls::with(['kecamatan', 'desa'])
                ->when($kecamatanId, fn($query) => $query->where('id_kec', $kecamatanId))
                ->when($q, function ($query) use ($q) {
                    $query->where('sls', 'like', "%{$q}%")
                          ->orWhere('id_sls', 'like', "%{$q}%")
                          ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$q}%"))
                          ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$q}%"));
                })
                ->limit(30)
                ->get();

            foreach ($slsList as $s) {
                $kecNama = $s->kecamatan?->nama ?? '-';
                $desaNama = $s->desa?->desa ?? '-';
                $code = $s->id_sls ? "[{$s->id_sls}] " : "";
                $results[] = [
                    'label' => "{$s->sls} (Kec {$kecNama}, Desa {$desaNama})",
                    'value' => "{$s->sls} (Kec {$kecNama}, Desa {$desaNama})",
                    'id' => $s->id,
                    'id_kec' => $s->id_kec,
                    'id_desa' => $s->id_desa,
                    'subtext' => "{$code}Kec. {$kecNama}, Desa {$desaNama}",
                ];
            }
        } elseif ($level === 'sub_sls') {
            $subList = SubSls::with(['kecamatan', 'desa'])
                ->when($kecamatanId, fn($query) => $query->where('id_kec', $kecamatanId))
                ->when($q, function ($query) use ($q) {
                    $query->where('subsls', 'like', "%{$q}%")
                          ->orWhere('id_sls', 'like', "%{$q}%")
                          ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$q}%"))
                          ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$q}%"));
                })
                ->limit(30)
                ->get();

            foreach ($subList as $sub) {
                $kecNama = $sub->kecamatan?->nama ?? '-';
                $desaNama = $sub->desa?->desa ?? '-';
                $results[] = [
                    'label' => "{$sub->subsls} (Kec {$kecNama}, Desa {$desaNama})",
                    'value' => "{$sub->subsls} (Kec {$kecNama}, Desa {$desaNama})",
                    'id' => $sub->id,
                    'id_kec' => $sub->id_kec,
                    'id_desa' => $sub->id_desa,
                    'subtext' => "Kec. {$kecNama}, Desa {$desaNama}",
                ];
            }
        } elseif ($level === 'nks') {
            $nksList = Nks::with(['kecamatan', 'desa'])
                ->when($kecamatanId, fn($query) => $query->where('id_kec', $kecamatanId))
                ->when($q, function ($query) use ($q) {
                    $query->where('nks', 'like', "%{$q}%")
                          ->orWhere('sls', 'like', "%{$q}%")
                          ->orWhereHas('desa', fn($dq) => $dq->where('desa', 'like', "%{$q}%"))
                          ->orWhereHas('kecamatan', fn($kq) => $kq->where('nama', 'like', "%{$q}%"));
                })
                ->limit(30)
                ->get();

            foreach ($nksList as $n) {
                $kecNama = $n->kecamatan?->nama ?? '-';
                $desaNama = $n->desa?->desa ?? '-';
                $slsText = $n->sls ? " - {$n->sls}" : "";
                $results[] = [
                    'label' => "NKS {$n->nks}{$slsText} (Kec {$kecNama}, Desa {$desaNama})",
                    'value' => "NKS {$n->nks}{$slsText} (Kec {$kecNama}, Desa {$desaNama})",
                    'id' => $n->id,
                    'id_kec' => $n->id_kec,
                    'id_desa' => $n->id_desa,
                    'subtext' => "Kec. {$kecNama}, Desa {$desaNama}",
                ];
            }
        }

        return response()->json($results);
    }
}
