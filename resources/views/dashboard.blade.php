@extends('layouts.app')

@section('title', 'Dashboard Utama')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        background-color: #f8fafc;
        color: #334155;
        outline: none;
    }
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        background-color: #ffffff;
        border-color: #94a3b8;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 0.65rem !important;
        padding: 0.25rem 0.65rem !important;
        border: 1px solid transparent !important;
        font-size: 0.75rem !important;
        color: #475569 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #1e293b !important;
        color: #ffffff !important;
        border-color: #1e293b !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        border-color: #e2e8f0 !important;
    }
    table.dataTable.no-footer { border-bottom: 1px solid #f1f5f9; }
    table.dataTable thead th { border-bottom: 1px solid #e2e8f0 !important; }
</style>
@endpush

@section('content')
<div
    x-data="{
        bebanTab: 'organik',
        detailMitraModal: null,
        activeKegiatanGroup: null
    }"
    @open-mitra-detail.window="detailMitraModal = $event.detail"
    class="space-y-6"
>

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

    <!-- TABBED CARD: BEBAN KERJA ORGANIK vs MITRA (YAJRA DATATABLES) -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="bebanTab === 'organik' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <h2 class="text-sm font-bold text-slate-800">Monitoring Beban Kerja Petugas</h2>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Urutan beban jam kerja bulan <strong>{{ $bulanFilter->translatedFormat('F Y') }}</strong> &bull;
                    Organik: <strong>{{ $organikCount }}</strong> orang, Mitra: <strong>{{ $mitraCount }}</strong> orang
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center bg-slate-100/80 p-1 rounded-2xl border border-slate-200/60">
                    <button
                        type="button"
                        @click="bebanTab = 'organik'; window.adjustBebanTable('organik')"
                        :class="bebanTab === 'organik' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                    >
                        Beban Organik ({{ $organikCount }})
                    </button>
                    <button
                        type="button"
                        @click="bebanTab = 'mitra'; window.adjustBebanTable('mitra')"
                        :class="bebanTab === 'mitra' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                    >
                        Beban Mitra ({{ $mitraCount }})
                    </button>
                </div>

                <span class="px-2.5 py-1.5 text-[10px] font-bold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100">
                    Kuota: {{ $kuotaJamBulan }} Jam
                </span>
            </div>
        </div>

        <!-- TAB 1: BEBAN ORGANIK -->
        <div x-show="bebanTab === 'organik'" x-cloak class="overflow-x-auto">
            <table id="tbl-beban-organik" class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-3">Pegawai Organik</th>
                        <th class="py-3 px-3">Tim Kerja</th>
                        <th class="py-3 px-3 text-center">Tugas Aktif</th>
                        <th class="py-3 px-3 w-56">Progres Beban Jam</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <!-- TAB 2: BEBAN MITRA -->
        <div x-show="bebanTab === 'mitra'" x-cloak class="overflow-x-auto">
            <table id="tbl-beban-mitra" class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-3">Mitra Statistik</th>
                        <th class="py-3 px-3">Domisili</th>
                        <th class="py-3 px-3 text-center">Tugas Aktif</th>
                        <th class="py-3 px-3 w-56">Progres Beban Jam</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>

    <!-- CARD ALOKASI HONOR MITRA STATISTIK (YAJRA DATATABLES) -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4">
        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                    <h2 class="text-sm font-bold text-slate-800">Alokasi Honor Mitra Statistik</h2>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Mitra teralokasikan kegiatan bulan <strong>{{ $bulanFilter->translatedFormat('F Y') }}</strong>. Klik tombol Rincian untuk melihat detail tahapan.
                </p>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-800 border border-sky-100 self-start sm:self-auto">
                Batas SBML: Rp {{ number_format($sbmlLimit, 0, ',', '.') }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table id="tbl-alokasi-honor" class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-3">Mitra Statistik</th>
                        <th class="py-3 px-3">Domisili &amp; Tahapan</th>
                        <th class="py-3 px-3 text-right">Total Honor</th>
                        <th class="py-3 px-3 w-56">Indikator SBML</th>
                        <th class="py-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
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

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dtLanguage = {
        processing: 'Memuat data...',
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
        infoEmpty: 'Menampilkan 0 data',
        infoFiltered: '(disaring dari _MAX_ total data)',
        zeroRecords: 'Tidak ada data yang sesuai',
        paginate: {
            first: 'Awal',
            last: 'Akhir',
            next: 'Berikutnya',
            previous: 'Sebelumnya'
        }
    };

    const escapeHtml = (str) => String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const formatRupiah = (val) => new Intl.NumberFormat('id-ID').format(Number(val || 0));

    const filterParams = {
        bulan: @json($bulanFilter->format('Y-m')),
        tim_kerja_id: @json($timKerjaId)
    };

    const renderBebanColumns = (isOrganik) => [
        {
            data: 'nama',
            name: 'nama',
            render: (data, type, row) => `
                <div>
                    <p class="font-bold text-slate-800">${escapeHtml(row.nama)}</p>
                    ${!isOrganik ? `<p class="text-[10px] font-mono text-slate-400">ID SOBAT: ${escapeHtml(row.id_sobat)}</p>` : ''}
                </div>
            `
        },
        {
            data: 'info_unit',
            name: 'info_unit',
            render: (data) => `<span class="text-slate-600 font-medium">${escapeHtml(data)}</span>`
        },
        {
            data: 'jumlah_tugas_aktif',
            name: 'jumlah_tugas_aktif',
            className: 'text-center',
            render: (data) => `<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold text-[11px]">${Number(data || 0)} tugas</span>`
        },
        {
            data: 'total_jam',
            name: 'total_jam',
            render: (data, type, row) => `
                <div class="space-y-1.5 min-w-[180px]">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-800">${row.total_jam} / ${row.kuota_jam} Jam</span>
                        <span class="font-semibold text-slate-500">${row.persentase_jam}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200/80 rounded-full overflow-hidden">
                        <div class="h-full ${escapeHtml(row.bar_class)} rounded-full transition-all" style="width: ${Math.min(100, Number(row.bar_persen || 0))}%"></div>
                    </div>
                </div>
            `
        },
        {
            data: 'status',
            name: 'status',
            className: 'text-center',
            render: (data, type, row) => `
                <span class="inline-block text-[10px] font-bold px-2.5 py-1 rounded-full border ${escapeHtml(row.badge_class)}">
                    ${escapeHtml(row.status)} (${row.persentase_jam}%)
                </span>
            `
        }
    ];

    const dtOrganik = $('#tbl-beban-organik').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('dashboard.beban_organik_data')),
            data: (d) => Object.assign(d, filterParams)
        },
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[3, 'desc']],
        language: dtLanguage,
        columns: renderBebanColumns(true)
    });

    const dtMitra = $('#tbl-beban-mitra').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('dashboard.beban_mitra_data')),
            data: (d) => Object.assign(d, filterParams)
        },
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[3, 'desc']],
        language: dtLanguage,
        columns: renderBebanColumns(false)
    });

    window.adjustBebanTable = function (tab) {
        setTimeout(() => {
            if (tab === 'organik') dtOrganik.columns.adjust();
            if (tab === 'mitra') dtMitra.columns.adjust();
        }, 50);
    };

    const dtHonor = $('#tbl-alokasi-honor').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('dashboard.alokasi_honor_data')),
            data: (d) => Object.assign(d, { bulan: filterParams.bulan })
        },
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[2, 'desc']],
        language: dtLanguage,
        columns: [
            {
                data: 'nama',
                name: 'nama',
                render: (data, type, row) => `
                    <div>
                        <p class="font-bold text-slate-800">${escapeHtml(row.nama)}</p>
                        <p class="text-[10px] font-mono text-slate-400">ID SOBAT: ${escapeHtml(row.id_sobat)}</p>
                    </div>
                `
            },
            {
                data: 'kecamatan_nama',
                name: 'kecamatan_nama',
                render: (data, type, row) => `
                    <div>
                        <p class="text-slate-700 font-medium">Kec. ${escapeHtml(row.kecamatan_nama)}</p>
                        <p class="text-[11px] text-slate-400">${Number(row.jumlah_tahapan || 0)} tahapan kegiatan</p>
                    </div>
                `
            },
            {
                data: 'total_honor',
                name: 'total_honor',
                className: 'text-right',
                render: (data) => `<span class="font-bold text-slate-800">Rp ${formatRupiah(data)}</span>`
            },
            {
                data: 'persentase_honor',
                name: 'persentase_honor',
                render: (data, type, row) => {
                    const barColor = row.is_over_sbml ? 'bg-rose-500' : (row.is_warning_sbml ? 'bg-amber-500' : 'bg-emerald-500');
                    const badgeHtml = row.is_over_sbml
                        ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200">&gt; SBML (+Rp ${formatRupiah(row.selisih_sbml)})</span>`
                        : (row.is_warning_sbml
                            ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">Mendekati SBML (${row.persentase_honor}%)</span>`
                            : `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Aman SBML (${row.persentase_honor}%)</span>`);
                    return `
                        <div class="space-y-1.5 min-w-[180px]">
                            <div class="flex justify-between items-center">${badgeHtml}</div>
                            <div class="w-full h-2 bg-slate-200/80 rounded-full overflow-hidden">
                                <div class="h-full ${barColor} rounded-full transition-all" style="width: ${Math.min(100, Number(row.persentase_honor || 0))}%"></div>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: () => `
                    <button type="button" class="btn-detail-mitra inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200/80 text-[11px] font-semibold transition cursor-pointer">
                        Rincian
                    </button>
                `
            }
        ]
    });

    $('#tbl-alokasi-honor tbody').on('click', '.btn-detail-mitra', function () {
        const rowData = dtHonor.row($(this).closest('tr')).data();
        if (rowData && rowData.detail_payload) {
            window.dispatchEvent(new CustomEvent('open-mitra-detail', { detail: rowData.detail_payload }));
        }
    });
});
</script>
@endpush
