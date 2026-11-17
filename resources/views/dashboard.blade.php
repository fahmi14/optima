@extends('layouts.app')

@section('title', 'Dashboard Eksekutif')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Dashboard OPTIMA 3307</h1>
            <p class="text-xs text-slate-500">Pemantauan beban kerja, progres kegiatan, dan serapan transport lokal BPS Kabupaten Wonosobo.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tugas-saya.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition">
                <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                <span>Tugas Saya</span>
            </a>
            @if(auth()->user()->hasAnyRole(['admin', 'katim']))
            <a href="{{ route('kegiatan.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Kelola Kegiatan</span>
            </a>
            @endif
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total Tugas Aktif</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ number_format($totalTugasAktif) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Tugas belum berstatus Selesai</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Mendekati Deadline (H-3)</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-amber-600 mt-3">{{ number_format($tugasMendekatiDeadline) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Jatuh tempo dalam 3 hari ke depan</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Tugas Overdue</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-rose-600 mt-3">{{ number_format($tugasOverdue) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Melewati batas tanggal deadline</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Serapan Transport Lokal</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800 mt-3">Rp {{ number_format($totalSerapanTransport, 0, ',', '.') }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Akumulasi alokasi SPJ aktif</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Peta Beban Kerja -->
        <div class="lg:col-span-7 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Peta Beban Kerja Pegawai &amp; Mitra</h2>
                    <p class="text-[11px] text-slate-400">Hijau: Aman (0–60%) &middot; Kuning: Padat (61–85%) &middot; Merah: Overload (&gt;85%)</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="kecamatan_id" value="{{ $kecamatanId }}">
                    <select name="tim_kerja_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700">
                        <option value="">Semua Tim Kerja</option>
                        @foreach($timKerjas as $tim)
                            <option value="{{ $tim->id }}" {{ $timKerjaId === $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                        @endforeach
                    </select>
                    <select name="tipe" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700">
                        <option value="">Organik &amp; Mitra</option>
                        <option value="organik" {{ $tipe === 'organik' ? 'selected' : '' }}>Pegawai Organik</option>
                        <option value="mitra" {{ $tipe === 'mitra' ? 'selected' : '' }}>Mitra Statistik</option>
                    </select>
                </form>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                @forelse($workloadMap as $row)
                    @php
                        $u = $row['user'];
                        $w = $row['workload'];
                    @endphp
                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $u->name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">
                                    {{ ucfirst($u->tipe) }} &middot; {{ $u->timKerja?->nama ?? 'Lintas Tim' }} &middot; {{ $w['jumlah_tugas_aktif'] }} tugas aktif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs font-bold text-slate-700">{{ $w['total_poin'] }}/{{ $w['kapasitas_maks'] }}</span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $w['badge_class'] }}">
                                    {{ $w['status'] }} ({{ $w['persentase'] }}%)
                                </span>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-slate-200/80 rounded-full overflow-hidden">
                            <div class="h-full {{ $w['bar_class'] }} rounded-full transition-all" style="width: {{ $w['bar_persen'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Tidak ada data pegawai/mitra.</p>
                @endforelse
            </div>
        </div>

        <!-- Jadwal Petugas Turun Lapangan per Kecamatan -->
        <div class="lg:col-span-5 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Jadwal Turun Lapangan</h2>
                    <p class="text-[11px] text-slate-400">Petugas berdasarkan kecamatan di Kab. Wonosobo</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}">
                    <input type="hidden" name="tim_kerja_id" value="{{ $timKerjaId }}">
                    <input type="hidden" name="tipe" value="{{ $tipe }}">
                    <select name="kecamatan_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatans as $kec)
                            <option value="{{ $kec->id }}" {{ $kecamatanId === $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
                @forelse($jadwalLapangan as $jadwal)
                    <div class="flex items-start justify-between gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50/70 transition">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700 text-center shrink-0">
                                <span class="block text-[10px] font-bold uppercase">{{ $jadwal->tgl_tugas->translatedFormat('M') }}</span>
                                <span class="block text-sm font-bold leading-none">{{ $jadwal->tgl_tugas->format('d') }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $jadwal->user?->name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $jadwal->periodeKegiatan?->masterKegiatan?->nama }} &middot; {{ $jadwal->penugasan?->subKegiatan?->nama }}</p>
                                <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-medium text-sage-700 bg-sage-50 px-2 py-0.5 rounded-md">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                                    Kec. {{ $jadwal->kecamatan?->nama ?? '-' }}
                                </span>
                            </div>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-700 shrink-0">
                            Rp {{ number_format($jadwal->nominal, 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada jadwal turun lapangan.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Progres Kegiatan Global -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800">Progres Kegiatan &amp; Serapan Pagu Transport</h2>
            <p class="text-[11px] text-slate-400">Ringkasan pelaksanaan kegiatan per periode</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase">
                        <th class="py-2.5 pr-4">Master Kegiatan</th>
                        <th class="py-2.5 pr-4">Tim Kerja</th>
                        <th class="py-2.5 pr-4">Periode</th>
                        <th class="py-2.5 pr-4">Tahapan / Tugas</th>
                        <th class="py-2.5 pr-4">Pagu Transport</th>
                        <th class="py-2.5">Serapan Transport</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kegiatans as $keg)
                        @forelse($keg->periodes as $per)
                            @php
                                $allTasks = $per->subKegiatans->flatMap->penugasans;
                                $doneTasks = $allTasks->where('status', 'Selesai')->count();
                                $totalTasks = $allTasks->count();
                                $terpakai = $per->alokasiTransports->where('status_spj', '!=', 'Ditolak')->sum('nominal');
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-3 pr-4 font-semibold text-slate-800">{{ $keg->nama }} ({{ $keg->tahun }})</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $keg->timKerja?->nama }}</td>
                                <td class="py-3 pr-4">
                                    <span class="font-medium text-slate-800">{{ $per->nama_periode }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $per->tgl_mulai->format('d/m/Y') }} - {{ $per->tgl_selesai->format('d/m/Y') }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">
                                    {{ $per->subKegiatans->count() }} tahapan &middot;
                                    <span class="font-semibold text-slate-800">{{ $doneTasks }}/{{ $totalTasks }}</span> tugas selesai
                                </td>
                                <td class="py-3 pr-4 text-slate-700">Rp {{ number_format($per->pagu_transport, 0, ',', '.') }}</td>
                                <td class="py-3 font-semibold text-emerald-700">Rp {{ number_format($terpakai, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-slate-800">{{ $keg->nama }} ({{ $keg->tahun }})</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $keg->timKerja?->nama }}</td>
                                <td colspan="4" class="py-3 text-slate-400 italic">Belum ada periode</td>
                            </tr>
                        @endforelse
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada data kegiatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
