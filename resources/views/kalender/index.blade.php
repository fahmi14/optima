@extends('layouts.app')

@section('title', 'Kalender Pekerjaan')

@section('content')
<div x-data="{ selectedEvent: null, activeTahapanIndex: 0 }" class="space-y-6">
    <!-- Header & Month Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kalender Pekerjaan</h1>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                @if($isManager)
                    Pemantauan jadwal kegiatan per tanggal. Klik nama kegiatan untuk melihat tahapan pelaksanaan &amp; daftar petugas.
                @else
                    Jadwal penugasan dan kegiatan kerja Anda sendiri per tanggal.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-2xs">
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $prevBulan])) }}" class="p-2 hover:bg-slate-100 rounded-xl text-slate-600 transition" title="Bulan Sebelumnya">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <span class="px-5 py-1.5 text-xs font-bold text-slate-800 min-w-[150px] text-center tracking-wide">
                {{ $currentMonth->translatedFormat('F Y') }}
            </span>
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $nextBulan])) }}" class="p-2 hover:bg-slate-100 rounded-xl text-slate-600 transition" title="Bulan Berikutnya">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Banner Info Kuota Kerja Bulanan & SBML (Pastel & Lega) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hari Kerja Bulan Ini</span>
                <p class="text-xl font-bold text-slate-800">{{ $infoBulan['hari_kerja'] }} <span class="text-xs font-normal text-slate-500">Hari</span></p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center shadow-2xs">
                HK
            </div>
        </div>

        <div class="bg-gradient-to-br from-sky-50/50 to-white border border-sky-100 rounded-2xl p-5 shadow-2xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[11px] font-bold text-sky-700/70 uppercase tracking-wider">Standar Kuota Jam Kerja</span>
                <p class="text-xl font-bold text-sky-800">{{ $infoBulan['kuota_jam'] }} <span class="text-xs font-normal text-sky-600">Jam ({{ $infoBulan['hari_kerja'] }} &times; 8 jam)</span></p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-sky-100/70 text-sky-700 flex items-center justify-center shadow-2xs">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-50/50 to-white border border-emerald-100 rounded-2xl p-5 shadow-2xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[11px] font-bold text-emerald-700/70 uppercase tracking-wider">Batas SBML Mitra</span>
                <p class="text-xl font-bold text-emerald-800">Rp {{ number_format($infoBulan['sbml'], 0, ',', '.') }} <span class="text-xs font-normal text-emerald-600">/ bln</span></p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center shadow-2xs">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar (Lega & Kalem) -->
    <form method="GET" action="{{ route('kalender.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-wrap items-center gap-3.5">
        <input type="month" name="bulan" value="{{ $bulan }}" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">

        @if($isManager)
            <select name="tim_kerja_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">
                <option value="">Semua Tim Kerja</option>
                @foreach($timKerjas as $tim)
                    <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                @endforeach
            </select>

            <select name="user_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">
                <option value="">Semua Petugas</option>
                @foreach($usersList as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->tipe) }})</option>
                @endforeach
            </select>

            <select name="kecamatan_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
                @endforeach
            </select>
        @endif

        <select name="jenis_tahapan" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">
            <option value="">Semua Jenis Tahapan</option>
            @foreach(['Teknis', 'Lapangan', 'Administrasi'] as $j)
                <option value="{{ $j }}" {{ request('jenis_tahapan') === $j ? 'selected' : '' }}>{{ $j }}</option>
            @endforeach
        </select>

        <select name="status" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none focus:ring-1 focus:ring-slate-400">
            <option value="">Semua Status</option>
            @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition shadow-2xs">Filter</button>
        <a href="{{ route('kalender.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">Reset</a>
    </form>

    <!-- Calendar Grid (Lega, Bersih, & Rapi) -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[850px]">
                <!-- Day Names -->
                <div class="grid grid-cols-7 bg-slate-50/70 border-b border-slate-100 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <div class="py-3.5">Senin</div>
                    <div class="py-3.5">Selasa</div>
                    <div class="py-3.5">Rabu</div>
                    <div class="py-3.5">Kamis</div>
                    <div class="py-3.5">Jumat</div>
                    <div class="py-3.5 text-amber-600/90">Sabtu</div>
                    <div class="py-3.5 text-rose-600/90">Minggu</div>
                </div>

                <!-- Days -->
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
                    @foreach($days as $day)
                        <div class="min-h-[135px] p-2.5 flex flex-col gap-2 {{ $day['in_month'] ? 'bg-white' : 'bg-slate-50/40 text-slate-300' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold {{ $day['is_today'] ? 'w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center shadow-xs' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-400') }}">
                                    {{ $day['day'] }}
                                </span>
                                @if(count($day['events']) > 0)
                                    <span class="text-[10px] font-semibold text-slate-400 px-1.5 py-0.5 bg-slate-100/70 rounded-md">{{ count($day['events']) }} kegiatan</span>
                                @endif
                            </div>

                            <div class="space-y-1.5 overflow-y-auto max-h-32 pr-1">
                                @foreach($day['events'] as $ev)
                                    <button
                                        type="button"
                                        @click="selectedEvent = {{ json_encode($ev) }}; activeTahapanIndex = 0"
                                        class="w-full text-left px-2.5 py-2 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-slate-100 hover:border-slate-300 text-slate-800 text-xs font-semibold leading-snug cursor-pointer transition flex items-center justify-between gap-1.5 group shadow-2xs"
                                        title="{{ $ev['nama_kegiatan'] }} ({{ $ev['total_petugas'] }} petugas)"
                                    >
                                        <div class="truncate flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500/80 group-hover:scale-110 transition-transform shrink-0"></span>
                                            <span class="truncate">{{ $ev['nama_kegiatan'] }}</span>
                                        </div>
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] bg-white border border-slate-200 text-slate-600 font-bold shrink-0">
                                            {{ $ev['total_petugas'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dialog Detail Kegiatan, Tahapan, & Daftar Petugas (Modern, Pastel, Lega) -->
    <div x-show="selectedEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="selectedEvent = null" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl max-h-[92vh] flex flex-col border border-slate-100" x-if="selectedEvent">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="space-y-1">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100/70">
                        Detail Kegiatan
                    </span>
                    <h3 class="text-xl font-bold text-slate-800 tracking-tight" x-text="selectedEvent.nama_kegiatan"></h3>
                    <p class="text-xs text-slate-500 flex items-center gap-2 pt-0.5">
                        <span x-text="selectedEvent.tim_kerja"></span> &bull; 
                        <span>Tanggal: <strong class="text-slate-700" x-text="selectedEvent.tanggal"></strong></span>
                    </p>
                </div>
                <button @click="selectedEvent = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Tab / List Tahapan Kegiatan -->
            <div class="space-y-2">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Tahapan yang Berjalan di Tanggal Ini:</div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(thp, tIdx) in (selectedEvent.tahapans || [])" :key="tIdx">
                        <button
                            type="button"
                            @click="activeTahapanIndex = tIdx"
                            :class="activeTahapanIndex === tIdx ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80 font-semibold'"
                            class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition"
                        >
                            <span x-text="thp.nama_tahapan"></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full" :class="activeTahapanIndex === tIdx ? 'bg-white/20 text-white' : 'bg-white text-slate-600 font-bold'" x-text="(thp.petugas || []).length"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Rincian Tahapan Terpilih & Daftar Petugas (Lega & Kalem) -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-1" x-data="{ currentTahapan() { return (selectedEvent.tahapans || [])[activeTahapanIndex] || {} } }">
                <!-- Info Tahapan Card -->
                <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/70 text-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800 text-sm" x-text="currentTahapan().nama_tahapan"></span>
                            <span class="text-slate-500 text-[11px] px-2 py-0.5 rounded-md bg-white border border-slate-200/60" x-text="currentTahapan().jenis"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">Periode: <span class="font-medium text-slate-600" x-text="currentTahapan().periode"></span></p>
                    </div>
                    <div class="text-right bg-white px-3.5 py-2 rounded-xl border border-slate-200/60 shadow-2xs">
                        <span class="text-[10px] font-medium text-slate-400 block uppercase tracking-wider">Estimasi Waktu</span>
                        <p class="font-bold text-slate-800 text-xs"><span x-text="currentTahapan().estimasi_jam_satuan"></span> Jam / Satuan</p>
                    </div>
                </div>

                <!-- Daftar Petugas pada Tahapan ini -->
                <div class="space-y-3">
                    <div class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                        <span>Daftar Petugas (<span x-text="(currentTahapan().petugas || []).length"></span>)</span>
                        <span class="text-[11px] font-normal text-slate-400">Indikator Jam Kerja Bulanan &amp; SBML Mitra</span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(pet, pIdx) in (currentTahapan().petugas || [])" :key="pIdx">
                            <div class="p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-slate-300 transition space-y-3 text-xs shadow-2xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="font-bold text-slate-800 flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full" :class="pet.tipe === 'mitra' ? 'bg-sky-500' : 'bg-emerald-500'"></span>
                                            <span class="text-sm font-bold text-slate-800" x-text="pet.nama"></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold capitalize tracking-wide" :class="pet.tipe === 'mitra' ? 'bg-sky-50 text-sky-700 border border-sky-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'" x-text="pet.tipe"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Wilayah: <strong class="text-slate-700" x-text="pet.kecamatan"></strong>
                                            <template x-if="pet.target">
                                                <span> &bull; Target: <span class="font-medium text-slate-700" x-text="pet.target"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                    <div class="text-right space-y-1">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700" x-text="pet.status"></span>
                                        <p class="text-[11px] text-slate-500 font-medium" x-text="pet.info"></p>
                                    </div>
                                </div>

                                <!-- Indikator Beban Kerja Petugas (Pastel) -->
                                <template x-if="pet.workload">
                                    <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px]">
                                        <!-- Indikator 1: Total Jam Kerja vs Kuota Bulan Ini -->
                                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500 font-medium">Beban Jam Kerja:</span>
                                                <span class="font-bold" :class="pet.workload.is_jam_aman ? 'text-emerald-700' : 'text-rose-600'">
                                                    <span x-text="pet.workload.total_jam"></span> / <span x-text="pet.workload.kuota_jam"></span> Jam
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200/70 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all" :class="pet.workload.is_jam_aman ? (pet.workload.persentase_jam > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'" :style="'width: ' + Math.min(100, pet.workload.persentase_jam) + '%'"></div>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-slate-400">
                                                <span>Status: <strong :class="pet.workload.is_jam_aman ? 'text-slate-700' : 'text-rose-600'" x-text="pet.workload.is_jam_aman ? 'Aman (≤ Kuota)' : 'Overload Jam'"></strong></span>
                                                <span class="font-semibold text-slate-600" x-text="pet.workload.persentase_jam + '%'"></span>
                                            </div>
                                        </div>

                                        <!-- Indikator 2: Akumulasi Honor vs SBML (Khusus Mitra) -->
                                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 space-y-1.5" x-show="pet.tipe === 'mitra'">
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500 font-medium">Batas SBML Mitra:</span>
                                                <span class="font-bold" :class="pet.workload.is_honor_aman ? 'text-emerald-700' : 'text-rose-600'">
                                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(pet.workload.total_honor)"></span>
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200/70 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all" :class="pet.workload.is_honor_aman ? (pet.workload.persentase_honor > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'" :style="'width: ' + Math.min(100, pet.workload.persentase_honor) + '%'"></div>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-slate-400">
                                                <span>Maks: Rp <span x-text="new Intl.NumberFormat('id-ID').format(pet.workload.sbml)"></span></span>
                                                <span class="font-bold" :class="pet.workload.is_honor_aman ? 'text-emerald-600' : 'text-rose-600'" x-text="pet.workload.is_honor_aman ? 'Aman (≤ SBML)' : 'Melebihi SBML!'"></span>
                                            </div>
                                        </div>

                                        <!-- Indikator Organik -->
                                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 flex items-center justify-between" x-show="pet.tipe !== 'mitra'">
                                            <div>
                                                <span class="text-slate-600 font-medium block">Pegawai Organik</span>
                                                <p class="text-[10px] text-slate-400 mt-0.5">Kapasitas jam kerja dinas BPS.</p>
                                            </div>
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border" :class="pet.workload.badge_class" x-text="pet.workload.status"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!currentTahapan().petugas || currentTahapan().petugas.length === 0">
                            <p class="text-xs text-slate-400 italic text-center py-6 bg-slate-50/60 rounded-2xl border border-slate-100">Belum ada data petugas pada tahapan ini.</p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="button" @click="selectedEvent = null" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition shadow-2xs">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
