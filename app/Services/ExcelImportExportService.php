<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\MitraKepka;
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
            'nip', 'nama_lengkap', 'email', 'jabatan', 'pangkat_golongan',
            'no_hp', 'alamat', 'nama_tim_kerja', 'kapasitas_maks_poin', 'password_default',
        ];
        $samples = [
            ['199001012015011001', 'Andi Pratama, S.ST', 'andi.pratama@bps.go.id', 'Statistisi Ahli Pertama', 'Penata Muda Tk. I / III/b', '081234567890', 'Jl. Jend. Sudirman No. 12, Wonosobo', 'Tim Statistik Sosial', 100, 'bps3307'],
            ['199205122018022002', 'Siti Nurhaliza, S.Tr.Stat', 'siti.nur@bps.go.id', 'Pranata Komputer Ahli Pertama', 'Penata Muda / III/a', '085612345678', 'Perum Purnamandala, Kertek', 'Tim IPDS', 100, 'bps3307'],
        ];

        return $this->streamExcel('template_pegawai_organik.xlsx', 'Template Organik', $headers, $samples);
    }

    public function downloadTemplateMitra(): StreamedResponse
    {
        $headers = [
            'Nama Lengkap', 'Alamat Detail', 'Alamat Provinsi', 'Alamat Kab/Kota',
            'Alamat Kecamatan', 'Alamat Desa/Kel', 'Tempat, Tanggal Lahir', 'Umur',
            'Jenis Kelamin', 'Status Kawin', 'Pendidikan', 'Pekerjaan',
            'Deskripsi Pekerjaan Lain', 'Terikat Kontrak KLDI Lain', 'Nama KLDI',
            'Keterangan KLDI', 'No Telp', 'SOBAT ID', 'Email',
        ];
        $samples = [
            [
                'Ratna Susanti', 'Gosono 02/07', '(33) JAWA TENGAH', '(07) WONOSOBO',
                '(080) KERTEK', '(007) KARANGLUHUR', 'WONOSOBO, 04 Mei 2001', 25,
                'Pr', '(1) Belum Kawin', 'Tamat SMA/Sederajat', 'Wiraswasta',
                '', 'Tidak', '', '', '+62 838-4381-3542', '330723110171', 'ratnarizqyana24@gmail.com',
            ],
        ];

        return $this->streamExcel('template_mitra_kepka.xlsx', 'Template Mitra KEPKA', $headers, $samples);
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
            'estimasi_jam_per_satuan', 'deadline_tugas', 'dapat_transport', 'kecamatan_tujuan',
            'tgl_tugas_transport', 'nominal_transport_per_hari',
        ];
        $samples = [
            ['Updating', '33072601001', 4, 'Dokumen', 7, '2026-02-12', 'Ya', 'Wonosobo', '2026-02-05, 2026-02-06', 150000],
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
            $pangkat = trim((string) ($row['pangkat_golongan'] ?? ''));
            $noHp = trim((string) ($row['no_hp'] ?? ''));
            $alamat = trim((string) ($row['alamat'] ?? ''));
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
                        'pangkat_golongan' => $pangkat ?: null,
                        'no_hp' => $noHp ?: null,
                        'alamat' => $alamat ?: null,
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

    public function importMitra(UploadedFile $file, ?int $tahun = null): array
    {
        @ini_set('max_execution_time', '300');
        $tahun = $tahun ?: (int) date('Y');

        $rows = $this->readRows($file);
        $kecMap = Kecamatan::all()->keyBy(fn ($k) => mb_strtolower(trim($k->nama)));
        $defaultPasswordHash = Hash::make('bps3307');

        $success = 0;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            $nama = trim((string) ($row['nama lengkap'] ?? $row['nama_mitra'] ?? ''));
            $sobatId = trim((string) ($row['sobat id'] ?? $row['id_sobat'] ?? ''));
            $emailRaw = trim((string) ($row['email'] ?? $row['email_atau_username'] ?? ''));
            $noTelp = trim((string) ($row['no telp'] ?? $row['no_hp'] ?? ''));
            $kecRaw = trim((string) ($row['alamat kecamatan'] ?? $row['kecamatan_domisili'] ?? ''));

            if ($nama === '') {
                $errors[] = "Baris {$rowNum}: Nama Lengkap wajib diisi.";
                continue;
            }

            if ($emailRaw === '' && $sobatId === '') {
                $errors[] = "Baris {$rowNum} ({$nama}): Email atau SOBAT ID harus terisi salah satu.";
                continue;
            }

            // Normalisasi format "(080) KERTEK" -> "kertek"
            $kecClean = trim((string) preg_replace('/^\(\d+\)\s*/', '', $kecRaw));
            $kec = $kecClean !== '' ? $kecMap->get(mb_strtolower($kecClean)) : null;

            $isEmail = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) !== false;
            $email = $isEmail ? mb_strtolower($emailRaw) : ($sobatId !== '' ? "{$sobatId}@mitra.bps3307.local" : mb_strtolower(preg_replace('/[^a-z0-9]/i', '', $nama)) . "{$rowNum}@mitra.bps3307.local");

            try {
                $user = User::where('email', $email)->first();
                if (!$user && $sobatId !== '') {
                    $user = User::where('id_sobat', $sobatId)->first();
                }

                if (!$user) {
                    $user = new User();
                    $user->email = $email;
                    $user->password = $defaultPasswordHash;
                    $user->role = 'mitra';
                    $user->tipe = 'mitra';
                    $user->kapasitas_maks = 100;
                }

                $user->name = $nama;
                $user->id_sobat = $sobatId !== '' ? $sobatId : $user->id_sobat;
                $user->no_hp = $noTelp !== '' ? $noTelp : $user->no_hp;
                $user->alamat = trim((string) ($row['alamat detail'] ?? '')) ?: $user->alamat;
                $user->kecamatan_id = $kec?->id ?? $user->kecamatan_id;
                $user->username = $user->username ?: ($sobatId !== '' ? $sobatId : null);
                $user->save();

                $umurRaw = trim((string) ($row['umur'] ?? ''));

                MitraKepka::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'tahun' => $tahun,
                    ],
                    [
                        'nama_lengkap' => $nama,
                        'posisi' => trim((string) ($row['posisi'] ?? '')) ?: null,
                        'status_seleksi' => trim((string) ($row['status seleksi'] ?? '')) ?: null,
                        'alamat_detail' => trim((string) ($row['alamat detail'] ?? '')) ?: null,
                        'alamat_provinsi' => trim((string) ($row['alamat provinsi'] ?? '')) ?: null,
                        'alamat_kab' => trim((string) ($row['alamat kab/kota'] ?? '')) ?: null,
                        'alamat_kecamatan' => $kecRaw !== '' ? $kecRaw : null,
                        'alamat_desa' => trim((string) ($row['alamat desa/kel'] ?? '')) ?: null,
                        'ttl' => trim((string) ($row['tempat, tanggal lahir'] ?? '')) ?: null,
                        'umur' => is_numeric($umurRaw) ? (int) $umurRaw : null,
                        'jenis_kelamin' => trim((string) ($row['jenis kelamin'] ?? '')) ?: null,
                        'status_kawin' => trim((string) ($row['status kawin'] ?? '')) ?: null,
                        'pendidikan' => trim((string) ($row['pendidikan'] ?? '')) ?: null,
                        'pekerjaan' => trim((string) ($row['pekerjaan'] ?? '')) ?: null,
                        'deskripsi_pekerjaan_lain' => trim((string) ($row['deskripsi pekerjaan lain'] ?? '')) ?: null,
                        'terikat_kldi' => trim((string) ($row['terikat kontrak kldi lain'] ?? '')) ?: null,
                        'nama_kldi' => trim((string) ($row['nama kldi'] ?? '')) ?: null,
                        'keterangan_kldi' => trim((string) ($row['keterangan kldi'] ?? '')) ?: null,
                        'no_telp' => $noTelp !== '' ? $noTelp : null,
                        'sobat_id' => $sobatId !== '' ? $sobatId : null,
                        'email' => $emailRaw !== '' ? $emailRaw : $email,
                    ]
                );

                $success++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNum} ({$nama}): Gagal menyimpan ({$e->getMessage()}).";
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
            $jamPerSatuan = isset($row['estimasi_jam_per_satuan']) && is_numeric($row['estimasi_jam_per_satuan'])
                ? (float) $row['estimasi_jam_per_satuan']
                : ((float) ($sub->estimasi_jam_per_satuan ?: 1));
            $honorSatuan = (float) ($sub->honor_per_satuan ?: 0);
            $estimasiJamTotal = round($jamPerSatuan * $targetVol, 2);
            $honorTotal = round($honorSatuan * $targetVol, 2);

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

            $bobot = (int) round($estimasiJamTotal);

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
                DB::transaction(function () use ($sub, $user, $kec, $targetVol, $satuan, $bobot, $estimasiJamTotal, $honorSatuan, $honorTotal, $deadline, $needTransport, $dates, $nominal) {
                    $penugasan = Penugasan::create([
                        'sub_kegiatan_id' => $sub->id,
                        'user_id' => $user->id,
                        'kecamatan_id' => $kec?->id ?? $user->kecamatan_id,
                        'target_volume' => max(1, $targetVol),
                        'satuan_volume' => $satuan ?: 'Dokumen',
                        'bobot_poin' => $bobot,
                        'estimasi_jam_total' => $estimasiJamTotal,
                        'honor_satuan' => $honorSatuan,
                        'honor_total' => $honorTotal,
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
