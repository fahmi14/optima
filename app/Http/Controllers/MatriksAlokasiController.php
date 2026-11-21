<?php

namespace App\Http\Controllers;

use App\Models\AlokasiTransport;
use App\Models\Penugasan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatriksAlokasiController extends Controller
{
    public function index(Request $request): View
    {
        $tipe = $request->query('tipe', 'semua'); // semua | organik | mitra
        if (!in_array($tipe, ['semua', 'organik', 'mitra'], true)) {
            $tipe = 'semua';
        }

        $jenis = $request->query('jenis', 'semua'); // semua | translok | honor
        if (!in_array($jenis, ['semua', 'translok', 'honor'], true)) {
            $jenis = 'semua';
        }

        $defaultMonth = now()->format('Y-m');
        $bulanDariParam = $request->query('bulan_dari', $request->query('bulan', $defaultMonth));
        $bulanSampaiParam = $request->query('bulan_sampai', $request->query('bulan', $defaultMonth));

        try {
            $bulanDari = Carbon::createFromFormat('Y-m', $bulanDariParam)->startOfMonth();
        } catch (\Throwable $e) {
            $bulanDari = now()->startOfMonth();
        }

        try {
            $bulanSampai = Carbon::createFromFormat('Y-m', $bulanSampaiParam)->startOfMonth();
        } catch (\Throwable $e) {
            $bulanSampai = $bulanDari->copy()->startOfMonth();
        }

        // Pastikan bulanDari <= bulanSampai
        if ($bulanDari->gt($bulanSampai)) {
            [$bulanDari, $bulanSampai] = [$bulanSampai, $bulanDari];
        }

        $startDate = $bulanDari->copy()->startOfMonth();
        $endDate = $bulanSampai->copy()->endOfMonth();

        // Daftar pilihan bulan untuk dropdown (Tahun lalu s/d Tahun depan agar fleksibel)
        $tahunBase = (int) now()->format('Y');
        $bulanOptions = [];
        for ($y = $tahunBase - 1; $y <= $tahunBase + 1; $y++) {
            for ($m = 1; $m <= 12; $m++) {
                $dt = Carbon::create($y, $m, 1);
                $bulanOptions[] = [
                    'value' => $dt->format('Y-m'),
                    'label' => $dt->translatedFormat('F Y'),
                ];
            }
        }

        // 1. Ambil seluruh Penugasan (Honor) yang jatuh pada rentang bulan ini
        $penugasans = Penugasan::with([
            'user:id,name,tipe,role,nip,id_sobat,tim_kerja_id,kecamatan_id',
            'user.kecamatan:id,nama',
            'pengawas:id,name',
            'subKegiatan:id,periode_kegiatan_id,nama,honor_per_satuan',
            'subKegiatan.periodeKegiatan:id,master_kegiatan_id,nama_periode,tgl_mulai,tgl_selesai',
            'subKegiatan.periodeKegiatan.masterKegiatan:id,nama,tahun,tim_kerja_id',
        ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('deadline_tugas', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNull('deadline_tugas')
                            ->whereHas('subKegiatan.periodeKegiatan', function ($pq) use ($startDate, $endDate) {
                                $pq->whereDate('tgl_mulai', '<=', $endDate)
                                    ->whereDate('tgl_selesai', '>=', $startDate);
                            });
                    });
            })
            ->get();

        // 2. Ambil seluruh AlokasiTransport (Translok) pada rentang bulan ini (tidak ditolak)
        $transports = AlokasiTransport::with([
            'user:id,name,tipe,role,nip,id_sobat,tim_kerja_id,kecamatan_id',
            'user.kecamatan:id,nama',
            'kecamatan:id,nama',
            'periodeKegiatan:id,master_kegiatan_id,nama_periode,tgl_mulai,tgl_selesai',
            'periodeKegiatan.masterKegiatan:id,nama,tahun,tim_kerja_id',
            'penugasan:id,sub_kegiatan_id',
            'penugasan.subKegiatan:id,nama',
        ])
            ->where('status_spj', '!=', 'Ditolak')
            ->whereBetween('tgl_tugas', [$startDate, $endDate])
            ->get();

        // Kumpulkan daftar Kegiatan+Periode (kolom) & Petugas (baris) sesuai filter
        $periodesMap = []; // [periode_id => ['id', 'nama_kegiatan', 'nama_periode', 'tahun']]
        $petugasMap = [];  // [user_id => ['id', 'name', 'tipe', 'identitas', 'kecamatan']]
        $matrix = [];      // [user_id][periode_id] => ['honor' => 0, 'translok' => 0, 'hari_translok' => 0, 'volume_count' => 0, 'tahapans' => [...], 'details' => []]

        $initCell = fn () => [
            'honor' => 0.0,
            'translok' => 0.0,
            'hari_translok' => 0,
            'volume_count' => 0,
            'tahapans' => [],
            'details' => [],
        ];

        $initTahapan = fn (string $namaTahapan) => [
            'nama' => $namaTahapan,
            'honor' => 0.0,
            'translok' => 0.0,
            'hari_translok' => 0,
            'volume' => 0,
        ];

        // Proses Honor dari Penugasan
        if ($jenis === 'semua' || $jenis === 'honor') {
            foreach ($penugasans as $p) {
                $u = $p->user;
                $periode = $p->subKegiatan?->periodeKegiatan;
                $mk = $periode?->masterKegiatan;
                if (!$u || !$periode || !$mk) {
                    continue;
                }
                if ($tipe !== 'semua' && $u->tipe !== $tipe) {
                    continue;
                }

                $satuanHonor = (float) ($p->honor_satuan ?: ($p->subKegiatan?->honor_per_satuan ?? 0));
                $honorNominal = (float) ($p->honor_total ?: ($satuanHonor * (int) $p->target_volume));

                if ($jenis === 'honor' && $honorNominal <= 0) {
                    continue;
                }

                $perId = $periode->id;
                $periodesMap[$perId] = [
                    'id' => $perId,
                    'nama_kegiatan' => $mk->nama,
                    'nama_periode' => $periode->nama_periode,
                    'tahun' => $mk->tahun,
                ];

                $petugasMap[$u->id] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'tipe' => $u->tipe,
                    'identitas' => $u->nip ?: ($u->id_sobat ?: '-'),
                    'kecamatan' => $u->kecamatan?->nama ?? '-',
                ];

                if (!isset($matrix[$u->id][$perId])) {
                    $matrix[$u->id][$perId] = $initCell();
                }

                $tahapId = $p->subKegiatan?->id ?? 0;
                $tahapNama = $p->subKegiatan?->nama ?? 'Tahapan';
                if (!isset($matrix[$u->id][$perId]['tahapans'][$tahapId])) {
                    $matrix[$u->id][$perId]['tahapans'][$tahapId] = $initTahapan($tahapNama);
                }

                $matrix[$u->id][$perId]['honor'] += $honorNominal;
                $matrix[$u->id][$perId]['volume_count'] += (int) $p->target_volume;
                $matrix[$u->id][$perId]['tahapans'][$tahapId]['honor'] += $honorNominal;
                $matrix[$u->id][$perId]['tahapans'][$tahapId]['volume'] += (int) $p->target_volume;

                $pengawasInfo = $p->pengawas ? (' • Pengawas: ' . $p->pengawas->name) : '';
                $matrix[$u->id][$perId]['details'][] = [
                    'kategori' => 'Honor',
                    'tahapan' => $tahapNama,
                    'periode' => $periode->nama_periode,
                    'info' => $p->target_volume . ' ' . $p->satuan_volume . $pengawasInfo,
                    'nominal' => $honorNominal,
                ];
            }
        }

        // Proses Translok dari AlokasiTransport
        if ($jenis === 'semua' || $jenis === 'translok') {
            foreach ($transports as $tr) {
                $u = $tr->user;
                $periode = $tr->periodeKegiatan;
                $mk = $periode?->masterKegiatan;
                if (!$u || !$periode || !$mk) {
                    continue;
                }
                if ($tipe !== 'semua' && $u->tipe !== $tipe) {
                    continue;
                }

                $perId = $periode->id;
                $periodesMap[$perId] = [
                    'id' => $perId,
                    'nama_kegiatan' => $mk->nama,
                    'nama_periode' => $periode->nama_periode,
                    'tahun' => $mk->tahun,
                ];

                $petugasMap[$u->id] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'tipe' => $u->tipe,
                    'identitas' => $u->nip ?: ($u->id_sobat ?: '-'),
                    'kecamatan' => $u->kecamatan?->nama ?? '-',
                ];

                if (!isset($matrix[$u->id][$perId])) {
                    $matrix[$u->id][$perId] = $initCell();
                }

                $tahapId = $tr->penugasan?->subKegiatan?->id ?? ('tr_' . $perId);
                $tahapNama = $tr->penugasan?->subKegiatan?->nama ?? 'Tugas Lapangan';
                if (!isset($matrix[$u->id][$perId]['tahapans'][$tahapId])) {
                    $matrix[$u->id][$perId]['tahapans'][$tahapId] = $initTahapan($tahapNama);
                }

                $nominalTr = (float) $tr->nominal;
                $matrix[$u->id][$perId]['translok'] += $nominalTr;
                $matrix[$u->id][$perId]['hari_translok'] += 1;
                $matrix[$u->id][$perId]['tahapans'][$tahapId]['translok'] += $nominalTr;
                $matrix[$u->id][$perId]['tahapans'][$tahapId]['hari_translok'] += 1;

                $matrix[$u->id][$perId]['details'][] = [
                    'kategori' => 'Translok',
                    'tahapan' => $tahapNama,
                    'periode' => $periode->nama_periode,
                    'info' => $tr->tgl_tugas->format('d/m/Y') . ' • Kec. ' . ($tr->kecamatan?->nama ?? '-'),
                    'nominal' => $nominalTr,
                ];
            }
        }

        // Urutkan kegiatan+periode dan petugas secara alfabetis
        $kegiatans = collect($periodesMap)->sortBy([
            ['nama_kegiatan', 'asc'],
            ['nama_periode', 'asc'],
        ])->values();
        $petugasList = collect($petugasMap)->sortBy('name')->values();

        // Hitung total per baris (petugas) dan total per kolom (kegiatan+periode)
        $rowTotals = [];
        $colTotals = [];
        $grandTotal = ['honor' => 0.0, 'translok' => 0.0, 'total' => 0.0];

        foreach ($kegiatans as $k) {
            $colTotals[$k['id']] = ['honor' => 0.0, 'translok' => 0.0, 'total' => 0.0];
        }

        foreach ($petugasList as $pet) {
            $uid = $pet['id'];
            $rHonor = 0.0;
            $rTranslok = 0.0;
            foreach ($kegiatans as $k) {
                $kid = $k['id'];
                if (isset($matrix[$uid][$kid])) {
                    // Normalisasi array tahapans agar berindex numerik rapi
                    $matrix[$uid][$kid]['tahapans'] = array_values($matrix[$uid][$kid]['tahapans']);
                    $cell = $matrix[$uid][$kid];
                    $rHonor += $cell['honor'];
                    $rTranslok += $cell['translok'];

                    $colTotals[$kid]['honor'] += $cell['honor'];
                    $colTotals[$kid]['translok'] += $cell['translok'];
                    $colTotals[$kid]['total'] += ($cell['honor'] + $cell['translok']);
                }
            }
            $rowTotals[$uid] = [
                'honor' => $rHonor,
                'translok' => $rTranslok,
                'total' => $rHonor + $rTranslok,
            ];
            $grandTotal['honor'] += $rHonor;
            $grandTotal['translok'] += $rTranslok;
            $grandTotal['total'] += ($rHonor + $rTranslok);
        }

        return view('matriks-alokasi.index', compact(
            'tipe',
            'jenis',
            'bulanDari',
            'bulanSampai',
            'bulanOptions',
            'kegiatans',
            'petugasList',
            'matrix',
            'rowTotals',
            'colTotals',
            'grandTotal'
        ));
    }
}
