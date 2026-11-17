<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\Penugasan;
use App\Models\PeriodeKegiatan;
use App\Models\SubKegiatan;
use App\Models\TimKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExcelImportExportService
{
    public function __construct(
        private readonly TransportValidationService $transportService
    ) {}

    public function downloadTemplateOrganik(): StreamedResponse
    {
        $headers = [
            'nip', 'nama_lengkap', 'email', 'jabatan',
            'nama_tim_kerja', 'kapasitas_maks_poin', 'password_default',
        ];
        $samples = [
            ['199001012015011001', 'Andi Pratama, S.ST', 'andi.pratama@bps.go.id', 'Statistisi Ahli Pertama', 'Tim Statistik Sosial', 100, 'bps3307'],
            ['199205122018022002', 'Siti Nurhaliza, S.Tr.Stat', 'siti.nur@bps.go.id', 'Pranata Komputer Ahli Pertama', 'Tim IPDS', 100, 'bps3307'],
        ];

        return $this->streamExcel('template_pegawai_organik.xlsx', 'Template Organik', $headers, $samples);
    }

    public function downloadTemplateMitra(): StreamedResponse
    {
        $headers = [
            'id_sobat', 'nama_mitra', 'email_atau_username',
            'no_hp', 'kecamatan_domisili', 'kapasitas_maks_poin',
        ];
        $samples = [
            ['33072026001', 'Ahmad Kurniawan', 'ahmad.kurniawan@gmail.com', '081234567891', 'Wonosobo', 100],
            ['33072026002', 'Dewi Lestari', 'dewi.sobat3307', '085612345678', 'Kertek', 100],
        ];

        return $this->streamExcel('template_mitra_statistik.xlsx', 'Template Mitra', $headers, $samples);
    }

    public function downloadTemplateKegiatan(): StreamedResponse
    {
        $headers = [
            'nama_tim_kerja', 'nama_master_kegiatan', 'tahun', 'nama_periode',
            'tgl_mulai_periode', 'tgl_selesai_periode', 'pagu_transport',
            'nama_sub_kegiatan', 'jenis_tahapan', 'deadline_tahapan',
        ];
        $samples = [
            ['Tim Statistik Sosial', 'Susenas 2026', 2026, 'Periode Maret', '2026-03-01', '2026-03-31', 25000000, 'Updating Rumah Tangga', 'Lapangan', '2026-03-10'],
            ['Tim Statistik Sosial', 'Susenas 2026', 2026, 'Periode Maret', '2026-03-01', '2026-03-31', 25000000, 'Pendataan Lapangan', 'Lapangan', '2026-03-25'],
            ['Tim Statistik Sosial', 'Susenas 2026', 2026, 'Periode Maret', '2026-03-01', '2026-03-31', 25000000, 'Pengolahan & Anomali', 'Teknis', '2026-03-30'],
        ];

        return $this->streamExcel('template_kegiatan_tahapan.xlsx', 'Template Kegiatan', $headers, $samples);
    }

    public function downloadTemplatePenugasan(): StreamedResponse
    {
        $headers = [
            'nama_sub_kegiatan', 'nip_atau_id_sobat', 'target_volume', 'satuan_volume',
            'bobot_poin', 'deadline_tugas', 'dapat_transport', 'kecamatan_tujuan',
            'tgl_tugas_transport', 'nominal_transport_per_hari',
        ];
        $samples = [
            ['Updating', '33072601001', 4, 'BS', 3, '2026-02-12', 'Ya', 'Wonosobo', '2026-02-05, 2026-02-06', 150000],
            ['Pendataan Lapangan', '199103032014031001', 10, 'Ruta', 3, '2026-02-20', 'Tidak', 'Kertek', '', 0],
        ];

        return $this->streamExcel('template_distribusi_penugasan.xlsx', 'Template Penugasan', $headers, $samples);
    }

    public function importOrganik(UploadedFile $file): array
    {
        $rows = $this->readRows($file);
        $timMap = TimKerja::all()->keyBy(fn ($t) => mb_strtolower(trim($t->nama)));

        $success = 0;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            $nip = trim((string) ($row['nip'] ?? ''));
            $nama = trim((string) ($row['nama_lengkap'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $jabatan = trim((string) ($row['jabatan'] ?? ''));
            $namaTim = trim((string) ($row['nama_tim_kerja'] ?? ''));
            $kapasitas = (int) ($row['kapasitas_maks_poin'] ?? 100);
            $password = trim((string) ($row['password_default'] ?? 'bps3307'));

            if ($nama === '' || $email === '' || $nip === '') {
                $errors[] = "Baris {$rowNum}: NIP, nama_lengkap, dan email wajib diisi.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$rowNum}: Format email '{$email}' tidak valid.";
                continue;
            }

            $tim = $namaTim !== '' ? $timMap->get(mb_strtolower($namaTim)) : null;
            if ($namaTim !== '' && !$tim) {
                $errors[] = "Baris {$rowNum}: Tim Kerja '{$namaTim}' tidak ditemukan.";
                continue;
            }

            try {
                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $nama,
                        'username' => $nip,
                        'password' => Hash::make($password ?: 'bps3307'),
                        'role' => 'organik',
                        'tipe' => 'organik',
                        'nip' => $nip,
                        'jabatan' => $jabatan ?: null,
                        'tim_kerja_id' => $tim?->id,
                        'kapasitas_maks' => $kapasitas > 0 ? $kapasitas : 100,
                    ]
                );
                $success++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNum}: Gagal menyimpan ({$e->getMessage()}).";
            }
        }

        return ['success_count' => $success, 'errors' => $errors];
    }

    public function importMitra(UploadedFile $file): array
    {
        $rows = $this->readRows($file);
        $kecMap = Kecamatan::all()->keyBy(fn ($k) => mb_strtolower(trim($k->nama)));

        $success = 0;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            $idSobat = trim((string) ($row['id_sobat'] ?? ''));
            $nama = trim((string) ($row['nama_mitra'] ?? ''));
            $identifier = trim((string) ($row['email_atau_username'] ?? ''));
            $noHp = trim((string) ($row['no_hp'] ?? ''));
            $kecNama = trim((string) ($row['kecamatan_domisili'] ?? ''));
            $kapasitas = (int) ($row['kapasitas_maks_poin'] ?? 100);

            if ($idSobat === '' || $nama === '' || $identifier === '') {
                $errors[] = "Baris {$rowNum}: id_sobat, nama_mitra, dan email_atau_username wajib diisi.";
                continue;
            }

            $kec = $kecNama !== '' ? $kecMap->get(mb_strtolower($kecNama)) : null;
            if ($kecNama !== '' && !$kec) {
                $errors[] = "Baris {$rowNum}: Kecamatan '{$kecNama}' tidak ditemukan di Kabupaten Wonosobo.";
                continue;
            }

            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
            $email = $isEmail ? $identifier : "{$identifier}@mitra.bps3307.local";
            $username = $isEmail ? $idSobat : $identifier;

            try {
                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $nama,
                        'username' => $username,
                        'password' => Hash::make($idSobat),
                        'role' => 'mitra',
                        'tipe' => 'mitra',
                        'id_sobat' => $idSobat,
                        'no_hp' => $noHp ?: null,
                        'kecamatan_id' => $kec?->id,
                        'kapasitas_maks' => $kapasitas > 0 ? $kapasitas : 100,
                    ]
                );
                $success++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNum}: Gagal menyimpan ({$e->getMessage()}).";
            }
        }

        return ['success_count' => $success, 'errors' => $errors];
    }

    public function importKegiatan(UploadedFile $file): array
    {
        $rows = $this->readRows($file);
        $timMap = TimKerja::all()->keyBy(fn ($t) => mb_strtolower(trim($t->nama)));

        $success = 0;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            $namaTim = trim((string) ($row['nama_tim_kerja'] ?? ''));
            $namaMaster = trim((string) ($row['nama_master_kegiatan'] ?? ''));
            $tahun = (int) ($row['tahun'] ?? date('Y'));
            $namaPeriode = trim((string) ($row['nama_periode'] ?? ''));
            $tglMulai = $this->parseDate($row['tgl_mulai_periode'] ?? null);
            $tglSelesai = $this->parseDate($row['tgl_selesai_periode'] ?? null);
            $pagu = (float) ($row['pagu_transport'] ?? 0);
            $namaSub = trim((string) ($row['nama_sub_kegiatan'] ?? ''));
            $jenis = trim((string) ($row['jenis_tahapan'] ?? 'Teknis'));
            $deadline = $this->parseDate($row['deadline_tahapan'] ?? null);

            $tim = $timMap->get(mb_strtolower($namaTim));
            if (!$tim) {
                $errors[] = "Baris {$rowNum}: Tim Kerja '{$namaTim}' tidak ditemukan.";
                continue;
            }

            if ($namaMaster === '' || $namaPeriode === '' || $namaSub === '' || !$tglMulai || !$tglSelesai) {
                $errors[] = "Baris {$rowNum}: Master kegiatan, periode, tanggal periode, dan nama sub-kegiatan wajib diisi.";
                continue;
            }

            if (!in_array($jenis, ['Teknis', 'Lapangan', 'Administrasi'], true)) {
                $errors[] = "Baris {$rowNum}: jenis_tahapan '{$jenis}' harus Teknis, Lapangan, atau Administrasi.";
                continue;
            }

            try {
                DB::transaction(function () use ($tim, $namaMaster, $tahun, $namaPeriode, $tglMulai, $tglSelesai, $pagu, $namaSub, $jenis, $deadline) {
                    $master = MasterKegiatan::firstOrCreate(
                        [
                            'tim_kerja_id' => $tim->id,
                            'nama' => $namaMaster,
                            'tahun' => $tahun,
                        ]
                    );

                    $periode = PeriodeKegiatan::firstOrCreate(
                        [
                            'master_kegiatan_id' => $master->id,
                            'nama_periode' => $namaPeriode,
                        ],
                        [
                            'tgl_mulai' => $tglMulai,
                            'tgl_selesai' => $tglSelesai,
                            'pagu_transport' => $pagu,
                        ]
                    );

                    $urutan = $periode->subKegiatans()->count() + 1;
                    SubKegiatan::firstOrCreate(
                        [
                            'periode_kegiatan_id' => $periode->id,
                            'nama' => $namaSub,
                        ],
                        [
                            'jenis_tahapan' => $jenis,
                            'deadline_tahapan' => $deadline,
                            'urutan' => $urutan,
                        ]
                    );
                });
                $success++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNum}: Gagal menyimpan ({$e->getMessage()}).";
            }
        }

        return ['success_count' => $success, 'errors' => $errors];
    }

    public function importPenugasan(UploadedFile $file, PeriodeKegiatan $periode): array
    {
        $rows = $this->readRows($file);
        $subs = $periode->subKegiatans()->get()->keyBy(fn ($s) => mb_strtolower(trim($s->nama)));
        $kecMap = Kecamatan::all()->keyBy(fn ($k) => mb_strtolower(trim($k->nama)));

        $success = 0;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            $namaSub = trim((string) ($row['nama_sub_kegiatan'] ?? ''));
            $identitas = trim((string) ($row['nip_atau_id_sobat'] ?? ''));
            $targetVol = (int) ($row['target_volume'] ?? 1);
            $satuan = trim((string) ($row['satuan_volume'] ?? 'Dokumen'));
            $bobot = (int) ($row['bobot_poin'] ?? 1);
            $deadline = $this->parseDate($row['deadline_tugas'] ?? null);
            $dapatTransport = mb_strtolower(trim((string) ($row['dapat_transport'] ?? 'tidak')));
            $kecNama = trim((string) ($row['kecamatan_tujuan'] ?? ''));
            $tglRaw = trim((string) ($row['tgl_tugas_transport'] ?? ''));
            $nominal = (float) ($row['nominal_transport_per_hari'] ?? 0);

            $sub = $subs->get(mb_strtolower($namaSub));
            if (!$sub) {
                $errors[] = "Baris {$rowNum}: Sub-kegiatan '{$namaSub}' tidak ditemukan pada periode {$periode->nama_periode}.";
                continue;
            }

            $user = User::where('nip', $identitas)
                ->orWhere('id_sobat', $identitas)
                ->orWhere('email', $identitas)
                ->orWhere('username', $identitas)
                ->first();

            if (!$user) {
                $errors[] = "Baris {$rowNum}: Pegawai/Mitra dengan NIP/ID SOBAT '{$identitas}' tidak ditemukan.";
                continue;
            }

            $kec = $kecNama !== '' ? $kecMap->get(mb_strtolower($kecNama)) : null;
            if ($kecNama !== '' && !$kec) {
                $errors[] = "Baris {$rowNum}: Kecamatan '{$kecNama}' tidak ditemukan.";
                continue;
            }

            if (!in_array($bobot, [1, 3, 5], true)) {
                $bobot = 1;
            }

            $needTransport = in_array($dapatTransport, ['ya', 'yes', '1', 'true'], true);
            $dates = [];
            if ($needTransport) {
                if ($tglRaw === '') {
                    $errors[] = "Baris {$rowNum}: tgl_tugas_transport wajib diisi jika dapat_transport = Ya.";
                    continue;
                }
                foreach (explode(',', $tglRaw) as $part) {
                    $parsed = $this->parseDate(trim($part));
                    if (!$parsed) {
                        $errors[] = "Baris {$rowNum}: Format tanggal transport '{$part}' tidak valid (gunakan YYYY-MM-DD).";
                        continue 2;
                    }
                    $dates[] = $parsed;
                }
            }

            try {
                DB::transaction(function () use ($sub, $user, $kec, $targetVol, $satuan, $bobot, $deadline, $needTransport, $dates, $nominal) {
                    $penugasan = Penugasan::create([
                        'sub_kegiatan_id' => $sub->id,
                        'user_id' => $user->id,
                        'kecamatan_id' => $kec?->id ?? $user->kecamatan_id,
                        'target_volume' => max(1, $targetVol),
                        'satuan_volume' => $satuan ?: 'Dokumen',
                        'bobot_poin' => $bobot,
                        'deadline_tugas' => $deadline ?? $sub->deadline_tahapan,
                        'status' => 'Belum Mulai',
                    ]);

                    if ($needTransport) {
                        $this->transportService->allocateForPenugasan(
                            $penugasan,
                            $dates,
                            $nominal,
                            $kec?->id ?? $user->kecamatan_id
                        );
                    }
                });
                $success++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNum}: {$e->getMessage()}";
            }
        }

        return ['success_count' => $success, 'errors' => $errors];
    }

    public function exportRekapTransport(Collection $alokasis, string $filename = 'rekap_spj_transport_optima.xlsx'): StreamedResponse
    {
        $headers = [
            'No',
            'Nama Petugas',
            'NIP / ID SOBAT',
            'Tipe',
            'Tim Kerja',
            'Master Kegiatan',
            'Periode',
            'Sub-Kegiatan',
            'Kecamatan Tujuan',
            'Tanggal Tugas',
            'Nominal Transport (Rp)',
            'Status SPJ',
            'Catatan SPJ',
        ];

        $rows = [];
        foreach ($alokasis as $idx => $item) {
            $rows[] = [
                $idx + 1,
                $item->user?->name ?? '-',
                $item->user?->nip ?: ($item->user?->id_sobat ?: '-'),
                ucfirst($item->user?->tipe ?? '-'),
                $item->periodeKegiatan?->masterKegiatan?->timKerja?->nama ?? '-',
                $item->periodeKegiatan?->masterKegiatan?->nama ?? '-',
                $item->periodeKegiatan?->nama_periode ?? '-',
                $item->penugasan?->subKegiatan?->nama ?? '-',
                $item->kecamatan?->nama ?? '-',
                $item->tgl_tugas?->format('Y-m-d') ?? '-',
                (float) $item->nominal,
                $item->status_spj,
                $item->catatan_spj ?? '',
            ];
        }

        return $this->streamExcel($filename, 'Rekap SPJ Transport', $headers, $rows);
    }

    private function readRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray(null, true, true, false);

        if (count($raw) < 2) {
            return [];
        }

        $headerRow = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $raw[0]);
        $result = [];

        for ($i = 1; $i < count($raw); $i++) {
            $line = $raw[$i];
            $nonEmpty = array_filter($line, fn ($v) => $v !== null && trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            $assoc = [];
            foreach ($headerRow as $colIdx => $key) {
                if ($key !== '') {
                    $assoc[$key] = $line[$colIdx] ?? null;
                }
            }
            $result[$i + 1] = $assoc;
        }

        return $result;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse(trim((string) $value))->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function streamExcel(string $filename, string $sheetTitle, array $headers, array $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));

        foreach ($headers as $colIdx => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $header);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        foreach ($rows as $rowIdx => $row) {
            $excelRow = $rowIdx + 2;
            foreach (array_values($row) as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}{$excelRow}", $val);
            }
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
