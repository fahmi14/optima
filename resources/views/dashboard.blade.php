@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div x-data="{
    detailMitraModal: null,
    activeKegiatanGroup: null
}" class="space-y-6">

    <!-- Filter Bulan & Tim Kerja Global Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-2xs">
        <div>
            <h1 class="text-lg font-bold text-slate-800 tracking-tight">Ringkasan Beban &amp; Kegiatan</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Monitoring beban kerja pegawai organik, honor mitra statistik, dan jadwal tugas lapangan per bulan.
            </p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2.5">
            <input type="hidden" name="kecamatan_id" value="{{ $kecamatanId }}">
            <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                <input
                    type="month"
                    name="bulan"
                    value="{{ $bulanFilter->format('Y-m') }}"
                    onchange="this.form.submit()"
                    class="bg-transparent font-bold text-slate-700 focus:outline-none cursor-pointer"
                >
            </div>

            <select name="tim_kerja_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-semibold focus:outline-none">
                <option value="">Semua Tim Kerja</option>
                @foreach($timKerjas as $tim)
                    <option value="{{ $tim->id }}" {{ $timKerjaId === $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Ringkasan Statistik Kartu Atas -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tugas Aktif</span>
            <p class="text-xl font-bold text-slate-800 mt-1">{{ $totalTugasAktif }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Sedang berjalan / antrean</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">&le; 3 Hari Deadline</span>
            <p class="text-xl font-bold text-amber-600 mt-1">{{ $tugasMendekatiDeadline }}</p>
            <span class="text-[10px] text-amber-600/80 mt-0.5 block">Perlu percepatan</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Tugas Terlambat</span>
            <p class="text-xl font-bold text-rose-600 mt-1">{{ $tugasOverdue }}</p>
            <span class="text-[10px] text-rose-500 mt-0.5 block">Melewati batas waktu</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Serapan Transport</span>
            <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($totalSerapanTransport, 0, ',', '.') }}</p>
            <span class="text-[10px] text-emerald-600/80 mt-0.5 block">Realisasi SPJ Lapangan</span>
        </div>
    </div>

    <!-- 2 CARD UTAMA: BEBAN PEGAWAI ORGANIK vs BEBAN MITRA STATISTIK -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- CARD 1: Beban Pegawai Organik (Ditampilkan Semua Pegawai, Beban Terbesar di Atas) -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4 flex flex-col justify-between">
            <div class="space-y-1 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h2 class="text-sm font-bold text-slate-800">Beban Kerja Pegawai Organik</h2>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Urutan beban jam kerja terbesar bulan <strong>{{ $bulanFilter->translatedFormat('F Y') }}</strong> (Total: {{ $pegawaiBebanList->count() }} pegawai)
                    </p>
                </div>
                <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">
                    Kuota: {{ $kuotaJamBulan }} Jam
                </span>
            </div>

            <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1 flex-1">
                @forelse($pegawaiBebanList as $row)
                    @php
                        $u = $row['user'];
                        $w = $row['workload'];
                    @endphp
                    <div class="p-3.5 rounded-2xl border transition space-y-2 {{ $w['is_jam_aman'] ? ($w['persentase_jam'] > 80 ? 'bg-amber-50/40 border-amber-200/80' : 'bg-slate-50/60 border-slate-100 hover:border-slate-200') : 'bg-rose-50/50 border-rose-200/80' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ $u->timKerja?->nama ?? 'Lintas Tim' }} &bull; {{ $w['jumlah_tugas_aktif'] }} tugas aktif bulan ini
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-slate-800">{{ $w['total_jam'] }} / {{ $w['kuota_jam'] }} Jam</span>
                                <span class="block text-[10px] font-bold px-2 py-0.5 rounded-full border mt-0.5 {{ $w['badge_class'] }}">
                                    {{ $w['status'] }} ({{ $w['persentase_jam'] }}%)
                                </span>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-slate-200/80 rounded-full overflow-hidden">
                            <div class="h-full {{ $w['bar_class'] }} rounded-full transition-all" style="width: {{ $w['bar_persen'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-10 italic">Belum ada data pegawai organik.</p>
                @endforelse
            </div>
        </div>

        <!-- CARD 2: Beban Mitra Statistik (Hanya yang Sudah Teralokasikan, Urut Honor Terbesar, Indikator SBML Pastel) -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4 flex flex-col justify-between">
            <div class="space-y-1 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                        <h2 class="text-sm font-bold text-slate-800">Alokasi Honor Mitra Statistik</h2>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Mitra teralokasikan kegiatan bulan <strong>{{ $bulanFilter->translatedFormat('F Y') }}</strong> (Total: {{ $mitraBebanList->count() }} mitra)
                    </p>
                </div>
                <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-800 border border-sky-100">
                    Batas SBML: Rp {{ number_format($sbmlLimit, 0, ',', '.') }}
                </span>
            </div>

            <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1 flex-1">
                @forelse($mitraBebanList as $row)
                    @php
                        $u = $row['user'];
                        $w = $row['workload'];
                        $isOverSbml = $w['total_honor'] > $sbmlLimit;
                        $isWarningSbml = !$isOverSbml && $w['persentase_honor'] >= 80;

                        // Kelas Pastel Sesuai Kondisi Honor SBML
                        $cardBg = $isOverSbml 
                            ? 'bg-rose-50/60 border-rose-200/90 hover:border-rose-300' 
                            : ($isWarningSbml ? 'bg-amber-50/50 border-amber-200/80 hover:border-amber-300' : 'bg-emerald-50/40 border-emerald-200/70 hover:border-emerald-300');
                    @endphp
                    <div
                        @click="detailMitraModal = {{ json_encode(['user' => $u, 'workload' => $w, 'tasks' => $row['tasks']]) }}"
                        class="p-3.5 rounded-2xl border transition cursor-pointer shadow-2xs space-y-2 {{ $cardBg }}"
                        title="Klik untuk melihat rincian kegiatan dan tahapan mitra ini"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $u->name }}</p>
                                    <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    Domisili: Kec. {{ $u->kecamatan?->nama ?? '-' }} &bull; {{ count($row['tasks']) }} tahapan kegiatan
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-slate-800">
                                    Rp {{ number_format($w['total_honor'], 0, ',', '.') }}
                                </span>
                                @if($isOverSbml)
                                    <span class="block text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200 mt-0.5">
                                        &gt; SBML (+Rp {{ number_format($w['total_honor'] - $sbmlLimit, 0, ',', '.') }})
                                    </span>
                                @elseif($isWarningSbml)
                                    <span class="block text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 mt-0.5">
                                        Mendekati SBML ({{ $w['persentase_honor'] }}%)
                                    </span>
                                @else
                                    <span class="block text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 mt-0.5">
                                        Aman SBML ({{ $w['persentase_honor'] }}%)
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Progress Bar Honor Terhadap SBML -->
                        <div class="w-full h-2 bg-slate-200/80 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300 {{ $isOverSbml ? 'bg-rose-500' : ($isWarningSbml ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                style="width: {{ min(100, $w['persentase_honor']) }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 space-y-1 text-slate-400">
                        <i data-lucide="users" class="w-8 h-8 mx-auto stroke-[1.5] text-slate-300"></i>
                        <p class="text-xs">Belum ada mitra yang teralokasikan kegiatan pada bulan {{ $bulanFilter->translatedFormat('F Y') }}.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- JADWAL TURUN LAPANGAN GROUPING PER KEGIATAN & TAHAPAN -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Daftar Jadwal Turun Lapangan</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Dikelompokkan per kegiatan &amp; tahapan. Klik kegiatan untuk melihat daftar nama petugas dan wilayah tugasnya.
                </p>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                <input type="hidden" name="bulan" value="{{ $bulanFilter->format('Y-m') }}">
                <input type="hidden" name="tim_kerja_id" value="{{ $timKerjaId }}">
                <select name="kecamatan_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-semibold focus:outline-none">
                    <option value="">Semua Kecamatan Wilayah</option>
                    @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}" {{ $kecamatanId === $kec->id ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($groupedJadwalLapangan as $groupKey => $group)
                <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition shadow-2xs space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-slate-800 truncate" title="{{ $group['nama_group'] }}">
                                {{ $group['nama_group'] }}
                            </h3>
                            <span class="inline-flex items-center gap-1.5 mt-1 text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-lg border border-indigo-100">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>{{ $group['total_turlap'] }} Petugas Turlap</span>
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="activeKegiatanGroup = {{ json_encode($group) }}"
                            class="px-2.5 py-1 text-[11px] font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition cursor-pointer shrink-0"
                        >
                            Lihat Petugas
                        </button>
                    </div>

                    <!-- Cuplikan 2 Petugas Pertama -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-200/60 text-xs">
                        @foreach($group['items']->take(2) as $sample)
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-700 truncate max-w-[150px]">{{ $sample->user?->name }}</span>
                                <span class="text-slate-500 font-medium">Kec. {{ $sample->kecamatan?->nama ?? '-' }} &bull; {{ $sample->tgl_tugas->format('d/m') }}</span>
                            </div>
                        @endforeach
                        @if($group['total_turlap'] > 2)
                            <p class="text-[10px] text-slate-400 italic pt-0.5">+ {{ $group['total_turlap'] - 2 }} petugas lainnya</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-10 text-center text-xs text-slate-400 italic">
                    Belum ada jadwal tugas lapangan pada bulan {{ $bulanFilter->translatedFormat('F Y') }}.
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL RINCIAN KEGIATAN & TAHAPAN MITRA (KETIKA KARTU MITRA DI-KLIK) -->
    <div
        x-show="detailMitraModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all"
    >
        <div
            @click.away="detailMitraModal = null"
            class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto"
            x-if="detailMitraModal"
        >
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-800 border border-sky-100">
                        Rincian Beban &amp; Honor Mitra
                    </span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1" x-text="detailMitraModal.user.name"></h3>
                    <p class="text-xs text-slate-400">
                        ID SOBAT: <span class="font-mono text-slate-700 font-semibold" x-text="detailMitraModal.user.id_sobat || '-'"></span> &bull; 
                        Domisili: Kec. <span class="text-slate-700 font-medium" x-text="detailMitraModal.user.kecamatan ? detailMitraModal.user.kecamatan.nama : '-'"></span>
                    </p>
                </div>
                <button @click="detailMitraModal = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Banner Status SBML Mitra -->
            <div
                class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                :class="detailMitraModal.workload.total_honor > {{ $sbmlLimit }} 
                    ? 'bg-rose-50/70 border-rose-200 text-rose-800' 
                    : (detailMitraModal.workload.persentase_honor >= 80 ? 'bg-amber-50/70 border-amber-200 text-amber-800' : 'bg-emerald-50/70 border-emerald-200 text-emerald-800')"
            >
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider block">Akumulasi Honor Bulan Ini:</span>
                    <p class="text-lg font-bold">
                        Rp <span x-text="new Intl.NumberFormat('id-ID').format(detailMitraModal.workload.total_honor)"></span>
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase tracking-wider block">Batas SBML:</span>
                    <p class="text-xs font-semibold">Rp {{ number_format($sbmlLimit, 0, ',', '.') }}</p>
                    <span class="text-[10px] font-bold mt-0.5 inline-block" x-text="detailMitraModal.workload.total_honor > {{ $sbmlLimit }} ? 'Melebihi Batas SBML!' : 'Dalam Batas Aman SBML'"></span>
                </div>
            </div>

            <!-- Tabel Daftar Kegiatan & Tahapan yang Diikuti Mitra -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-slate-800">Daftar Kegiatan &amp; Tahapan Penugasan:</h4>
                <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3.5">Kegiatan &amp; Tahapan</th>
                                <th class="py-2.5 px-3.5 text-center">Beban</th>
                                <th class="py-2.5 px-3.5 text-right">Honor</th>
                                <th class="py-2.5 px-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(t, idx) in detailMitraModal.tasks" :key="idx">
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3.5">
                                        <p class="font-bold text-slate-800" x-text="t.kegiatan"></p>
                                        <p class="text-[11px] text-slate-500" x-text="t.tahapan"></p>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-center font-semibold text-slate-700" x-text="t.target"></td>
                                    <td class="py-2.5 px-3.5 text-right font-bold text-slate-800">
                                        Rp <span x-text="new Intl.NumberFormat('id-ID').format(t.honor)"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-center">
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700" x-text="t.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" @click="detailMitraModal = null" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL RINCIAN PETUGAS TURUN LAPANGAN (KETIKA KARTU KEGIATAN TURLAP DI-KLIK) -->
    <div
        x-show="activeKegiatanGroup"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all"
    >
        <div
            @click.away="activeKegiatanGroup = null"
            class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto"
            x-if="activeKegiatanGroup"
        >
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                        Daftar Petugas Turun Lapangan
                    </span>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight mt-1" x-text="activeKegiatanGroup.nama_group"></h3>
                    <p class="text-xs text-slate-400">
                        Total: <strong class="text-slate-700" x-text="activeKegiatanGroup.total_turlap"></strong> petugas terdaftar
                    </p>
                </div>
                <button @click="activeKegiatanGroup = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3.5">Nama Petugas</th>
                            <th class="py-2.5 px-3.5">Tipe</th>
                            <th class="py-2.5 px-3.5">Wilayah Tugas</th>
                            <th class="py-2.5 px-3.5">Tanggal Tugas</th>
                            <th class="py-2.5 px-3.5 text-right">Nominal SPJ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, idx) in activeKegiatanGroup.items" :key="idx">
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-3.5 font-bold text-slate-800" x-text="item.user ? item.user.name : '-'"></td>
                                <td class="py-2.5 px-3.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold capitalize" :class="item.user?.tipe === 'mitra' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700'" x-text="item.user ? item.user.tipe : '-'"></span>
                                </td>
                                <td class="py-2.5 px-3.5">
                                    <span class="font-semibold text-slate-700">Kec. <span x-text="item.kecamatan ? item.kecamatan.nama : '-'"></span></span>
                                </td>
                                <td class="py-2.5 px-3.5 font-mono text-slate-600" x-text="item.tgl_tugas ? item.tgl_tugas.substring(0, 10) : '-'"></td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-slate-800">
                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.nominal)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" @click="activeKegiatanGroup = null" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
