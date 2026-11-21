@extends('layouts.app')

@section('title', 'Kalender Pekerjaan')

@section('content')
<div x-data="{
    selectedEvent: null,
    selectedDayEvents: null,
    selectedDayLabel: '',
    activeTahapanIndex: 0,

    openDayModal(day) {
        if (!day.events || day.events.length === 0) return;
        if (day.events.length === 1) {
            this.selectedEvent = day.events[0];
            this.activeTahapanIndex = 0;
        } else {
            this.selectedDayLabel = day.formatted_date;
            this.selectedDayEvents = day.events;
        }
    }
}" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kalender Pekerjaan</h1>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                @if($isManager)
                    Menampilkan rentang jadwal kegiatan hingga tanggal deadline. Klik kegiatan atau tanggal untuk melihat detail tahapan &amp; petugas.
                @else
                    Jadwal penugasan dan kegiatan kerja Anda sendiri berdasarkan rentang pelaksanaan.
                @endif
            </p>
        </div>
    </div>

    <!-- Banner Info Kuota Kerja Bulanan & SBML -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hari Kerja Bulan Ini</span>
                <p class="text-xl font-bold text-slate-800">{{ $infoBulan['hari_kerja'] }} <span class="text-xs font-normal text-slate-500">Hari</span></p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center shadow-2xs">HK</div>
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

    <!-- Filter Bar (Tanpa Input Bulan, Tetap Pertahankan Bulan & Zoom) -->
    <form method="GET" action="{{ route('kalender.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-wrap items-center gap-3">
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="zoom" value="{{ $zoom }}">

        @if($isManager)
            <select name="tim_kerja_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none">
                <option value="">Semua Tim Kerja</option>
                @foreach($timKerjas as $tim)
                    <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                @endforeach
            </select>

            <select name="user_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none">
                <option value="">Semua Petugas</option>
                @foreach($usersList as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->tipe) }})</option>
                @endforeach
            </select>

            <select name="kecamatan_id" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none">
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
                @endforeach
            </select>
        @endif

        <select name="jenis_tahapan" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none">
            <option value="">Semua Jenis Tahapan</option>
            @foreach(['Teknis', 'Lapangan', 'Administrasi'] as $j)
                <option value="{{ $j }}" {{ request('jenis_tahapan') === $j ? 'selected' : '' }}>{{ $j }}</option>
            @endforeach
        </select>

        <select name="status" class="text-xs bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 transition focus:outline-none">
            <option value="">Semua Status</option>
            @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition shadow-2xs">Filter</button>
        <a href="{{ route('kalender.index', ['bulan' => $bulan, 'zoom' => $zoom]) }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">Reset</a>
    </form>

    <!-- LEGENDA KEGIATAN (NAMA KEGIATAN (TAHAPAN KEGIATAN)) -->
    @if(!empty($legends))
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mr-1">Legenda:</span>
            @foreach($legends as $leg)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold border {{ $leg['bg'] }} {{ $leg['border'] }} {{ $leg['text'] }}">
                    <span class="w-2.5 h-2.5 rounded-full {{ $leg['dot'] }}"></span>
                    <span>{{ $leg['label'] }}</span>
                </span>
            @endforeach
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 ml-auto">
                <span>★ Akhir Rentang = Deadline</span>
            </span>
        </div>
    @endif

    <!-- TOOLBAR NAVIGASI BULAN & ZOOM DI ATAS KALENDER -->
    @php
        $zoomIdx = array_search($zoom, $zoomOptions);
        $zoomInVal = $zoomIdx > 0 ? $zoomOptions[$zoomIdx - 1] : null;
        $zoomOutVal = $zoomIdx !== false && $zoomIdx < count($zoomOptions) - 1 ? $zoomOptions[$zoomIdx + 1] : null;
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white border border-slate-200/80 rounded-2xl p-3 shadow-2xs">
        <!-- Navigasi Prev / Judul Bulan / Next -->
        <div class="flex items-center gap-1.5">
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $prevBulan])) }}" class="p-2 bg-slate-50 hover:bg-slate-100 border border-slate-200/70 rounded-xl text-slate-600 transition" title="Bulan Sebelumnya">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <span class="px-4 py-1.5 text-xs sm:text-sm font-bold text-slate-800 min-w-[180px] text-center tracking-wide">
                {{ $headerTitle }}
            </span>
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $nextBulan])) }}" class="p-2 bg-slate-50 hover:bg-slate-100 border border-slate-200/70 rounded-xl text-slate-600 transition" title="Bulan Berikutnya">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- Kontrol Zoom In / Zoom Out (1, 2, 4, 9, 12 Bulan) -->
        <div class="flex flex-wrap items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200/70">
            <a
                href="{{ $zoomInVal ? route('kalender.index', array_merge(request()->query(), ['zoom' => $zoomInVal])) : '#' }}"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition {{ $zoomInVal ? 'text-slate-700 hover:bg-white hover:shadow-2xs' : 'text-slate-300 pointer-events-none' }}"
                title="Zoom In"
            >
                <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                <span class="hidden md:inline">Zoom In</span>
            </a>
            @foreach($zoomOptions as $zOpt)
                <a
                    href="{{ route('kalender.index', array_merge(request()->query(), ['zoom' => $zOpt])) }}"
                    class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition {{ $zoom === $zOpt ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-500 hover:bg-white hover:text-slate-800' }}"
                >
                    {{ $zOpt }} Bln
                </a>
            @endforeach
            <a
                href="{{ $zoomOutVal ? route('kalender.index', array_merge(request()->query(), ['zoom' => $zoomOutVal])) : '#' }}"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition {{ $zoomOutVal ? 'text-slate-700 hover:bg-white hover:shadow-2xs' : 'text-slate-300 pointer-events-none' }}"
                title="Zoom Out"
            >
                <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                <span class="hidden md:inline">Zoom Out</span>
            </a>
        </div>
    </div>

    <!-- CALENDAR GRID MULTI-ZOOM (1, 2, 4, 9, 12 BULAN) -->
    @php
        $gridColsClass = match($zoom) {
            2 => 'grid-cols-1 xl:grid-cols-2 gap-5',
            4 => 'grid-cols-1 md:grid-cols-2 gap-4',
            9 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4',
            12 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4',
            default => 'grid-cols-1 gap-6',
        };
    @endphp

    <div class="grid {{ $gridColsClass }}">
        @foreach($months as $monthObj)
            <div class="bg-white border border-slate-200/80 rounded-3xl shadow-2xs overflow-hidden flex flex-col">
                <!-- Judul Bulan -->
                <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800 tracking-wide">{{ $monthObj['title'] }}</h2>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $zoom === 1 ? 'Tampilan Penuh' : 'Ringkas' }}</span>
                </div>

                @if($zoom <= 2)
                    <!-- MODE 1 ATAU 2 BULAN (DETAIL PENUH) -->
                    <div class="overflow-x-auto">
                        <div class="{{ $zoom === 1 ? 'min-w-[850px]' : 'min-w-[560px]' }}">
                            <div class="grid grid-cols-7 bg-slate-50/60 border-b border-slate-100 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <div class="py-3">Sen</div>
                                <div class="py-3">Sel</div>
                                <div class="py-3">Rab</div>
                                <div class="py-3">Kam</div>
                                <div class="py-3">Jum</div>
                                <div class="py-3 text-amber-600/90">Sab</div>
                                <div class="py-3 text-rose-600/90">Min</div>
                            </div>

                            <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
                                @foreach($monthObj['days'] as $day)
                                    <div class="{{ $zoom === 1 ? 'min-h-[130px] p-2.5' : 'min-h-[105px] p-2' }} flex flex-col gap-1.5 {{ $day['in_month'] ? 'bg-white' : 'bg-slate-50/40 text-slate-300' }}">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold {{ $day['is_today'] ? 'w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center shadow-xs' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-400') }}">
                                                {{ $day['day'] }}
                                            </span>
                                            @if(count($day['events']) > 0)
                                                <span class="text-[9px] font-bold text-slate-400 px-1.5 py-0.5 bg-slate-100/80 rounded-md">{{ count($day['events']) }}</span>
                                            @endif
                                        </div>

                                        <div class="space-y-1 overflow-y-auto max-h-28 pr-0.5">
                                            @foreach($day['events'] as $ev)
                                                @php $st = $ev['style']; @endphp
                                                <button
                                                    type="button"
                                                    @click="selectedEvent = {{ json_encode($ev) }}; activeTahapanIndex = 0"
                                                    class="w-full text-left px-2 py-1.5 rounded-xl border {{ $st['bg'] }} {{ $st['border'] }} {{ $st['text'] }} hover:brightness-95 text-[11px] font-semibold leading-snug cursor-pointer transition flex items-center justify-between gap-1 shadow-2xs"
                                                    title="{{ $ev['nama_kegiatan'] }} ({{ $ev['rentang'] }})"
                                                >
                                                    <div class="truncate flex items-center gap-1.5">
                                                        <span class="w-2 h-2 rounded-full {{ $st['dot'] }} shrink-0"></span>
                                                        <span class="truncate">{{ $ev['nama_kegiatan'] }}</span>
                                                        @if($ev['is_deadline'])
                                                            <span class="text-[9px] font-extrabold text-rose-600 shrink-0" title="Deadline Akhir Rentang">★</span>
                                                        @endif
                                                    </div>
                                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[9px] bg-white/80 text-slate-700 font-bold shrink-0">
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
                @else
                    <!-- MODE ZOOM OUT (4, 9, 12 BULAN - MINI KALENDER PASTEL RAPI) -->
                    <div class="p-3 flex-1">
                        <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400 uppercase pb-1.5 border-b border-slate-100">
                            <div>S</div><div>S</div><div>R</div><div>K</div><div>J</div>
                            <div class="text-amber-500">S</div><div class="text-rose-500">M</div>
                        </div>
                        <div class="grid grid-cols-7 gap-1 pt-1.5">
                            @foreach($monthObj['days'] as $day)
                                @php $hasEvents = count($day['events']) > 0; @endphp
                                <button
                                    type="button"
                                    @if($hasEvents) @click="openDayModal({{ json_encode($day) }})" @endif
                                    class="min-h-[44px] p-1 rounded-xl border text-left flex flex-col justify-between transition {{ $day['in_month'] ? ($hasEvents ? 'bg-slate-50/70 border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/40 cursor-pointer' : 'bg-white border-transparent') : 'opacity-30 border-transparent' }}"
                                    title="{{ $hasEvents ? $day['formatted_date'] . ' (' . count($day['events']) . ' kegiatan)' : '' }}"
                                >
                                    <span class="text-[10px] font-bold leading-none {{ $day['is_today'] ? 'px-1.5 py-0.5 rounded-full bg-slate-800 text-white' : 'text-slate-700' }}">
                                        {{ $day['day'] }}
                                    </span>
                                    @if($hasEvents)
                                        <div class="flex flex-wrap gap-0.5 mt-1">
                                            @foreach(array_slice($day['events'], 0, 4) as $ev)
                                                <span class="w-2 h-2 rounded-full {{ $ev['style']['dot'] }} {{ $ev['is_deadline'] ? 'ring-1 ring-rose-500' : '' }}"></span>
                                            @endforeach
                                            @if(count($day['events']) > 4)
                                                <span class="text-[8px] font-bold text-slate-500">+</span>
                                            @endif
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- MODAL PILIH KEGIATAN PADA TANGGAL (UNTUK ZOOM OUT SAAT > 1 KEGIATAN) -->
    <div x-show="selectedDayEvents" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="selectedDayEvents = null" class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-100" x-if="selectedDayEvents">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase">Daftar Kegiatan Tanggal</span>
                    <h3 class="text-base font-bold text-slate-800" x-text="selectedDayLabel"></h3>
                </div>
                <button @click="selectedDayEvents = null" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                <template x-for="(ev, idx) in selectedDayEvents" :key="idx">
                    <button
                        type="button"
                        @click="selectedEvent = ev; activeTahapanIndex = 0; selectedDayEvents = null"
                        class="w-full text-left p-3.5 rounded-2xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition flex items-center justify-between gap-3 cursor-pointer"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-3 h-3 rounded-full shrink-0" :class="ev.style.dot"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate" x-text="ev.nama_kegiatan"></p>
                                <p class="text-[10px] text-slate-400" x-text="'Rentang: ' + ev.rentang"></p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-bold shrink-0" x-text="ev.total_petugas + ' Petugas'"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Dialog Detail Kegiatan, Tahapan, & Daftar Petugas (Modern, Pastel, Lega) -->
    <div x-show="selectedEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="selectedEvent = null" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl max-h-[92vh] flex flex-col border border-slate-100" x-if="selectedEvent">
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="space-y-1">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100/70">
                        Detail Kegiatan
                    </span>
                    <h3 class="text-xl font-bold text-slate-800 tracking-tight" x-text="selectedEvent.nama_kegiatan"></h3>
                    <p class="text-xs text-slate-500 flex flex-wrap items-center gap-2 pt-0.5">
                        <span x-text="selectedEvent.tim_kerja"></span> &bull;
                        <span>Tanggal: <strong class="text-slate-700" x-text="selectedEvent.tanggal"></strong></span> &bull;
                        <span>Rentang: <strong class="text-indigo-700" x-text="selectedEvent.rentang"></strong></span>
                    </p>
                </div>
                <button @click="selectedEvent = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-2">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Tahapan yang Berjalan:</div>
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

            <div class="flex-1 overflow-y-auto space-y-4 pr-1" x-data="{ currentTahapan() { return (selectedEvent.tahapans || [])[activeTahapanIndex] || {} } }">
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

                <div class="space-y-3">
                    <div class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                        <span>Daftar Petugas (<span x-text="(currentTahapan().petugas || []).length"></span>)</span>
                        <span class="text-[11px] font-normal text-slate-400">Indikator Jam Kerja, Honor &amp; Translok</span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(pet, pIdx) in (currentTahapan().petugas || [])" :key="pIdx">
                            <div class="p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-slate-300 transition space-y-3 text-xs shadow-2xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="font-bold text-slate-800 flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full" :class="pet.tipe === 'mitra' ? 'bg-emerald-500' : 'bg-sky-500'"></span>
                                            <span class="text-sm font-bold text-slate-800" x-text="pet.nama"></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold capitalize tracking-wide" :class="pet.tipe === 'mitra' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-sky-50 text-sky-700 border border-sky-200'" x-text="pet.tipe"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Wilayah: <strong class="text-slate-700" x-text="pet.kecamatan"></strong>
                                            <template x-if="pet.target">
                                                <span> &bull; Target: <span class="font-medium text-slate-700" x-text="pet.target"></span></span>
                                            </template>
                                        </p>
                                        <template x-if="pet.pengawas_nama">
                                            <p class="text-[11px] text-indigo-700 font-semibold">
                                                Pengawas (PML): <span x-text="pet.pengawas_nama"></span>
                                            </p>
                                        </template>
                                    </div>
                                    <div class="text-right space-y-1">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700" x-text="pet.status"></span>
                                        <p class="text-[11px] text-slate-500 font-medium" x-text="pet.info"></p>
                                    </div>
                                </div>

                                <template x-if="pet.workload">
                                    <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px]">
                                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500 font-medium">Beban Jam Kerja:</span>
                                                <span class="font-bold" :class="pet.workload.is_jam_aman ? 'text-emerald-700' : 'text-rose-600'">
                                                    <span x-text="pet.workload.total_jam"></span> / <span x-text="pet.workload.kuota_jam"></span> Jam
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200/70 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all" :class="pet.workload.is_jam_aman ? (pet.workload.persentase_jam > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'" :style="'width: ' + Math.min(100, Number(pet.workload.persentase_jam || 0)) + '%'"></div>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-slate-400">
                                                <span>Status: <strong :class="pet.workload.is_jam_aman ? 'text-slate-700' : 'text-rose-600'" x-text="pet.workload.is_jam_aman ? 'Aman (≤ Kuota)' : 'Overload Jam'"></strong></span>
                                                <span class="font-semibold text-slate-600" x-text="(pet.workload.persentase_jam || 0) + '%'"></span>
                                            </div>
                                        </div>

                                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500 font-medium">Honor Bulan Ini:</span>
                                                <span class="font-bold" :class="pet.workload.is_honor_aman ? 'text-emerald-700' : 'text-rose-600'">
                                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(pet.workload.total_honor || 0)"></span>
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200/70 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-300" :class="pet.workload.is_honor_aman ? ((pet.workload.persentase_honor || 0) > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'" :style="'width: ' + Math.min(100, Number(pet.workload.persentase_honor || 0)) + '%'"></div>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-slate-400">
                                                <span>SBML: Rp <span x-text="new Intl.NumberFormat('id-ID').format(pet.workload.sbml || 0)"></span></span>
                                                <span class="font-bold" :class="pet.workload.is_honor_aman ? 'text-emerald-600' : 'text-rose-600'" x-text="(pet.workload.persentase_honor || 0) + '%'"></span>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-2 p-3 rounded-xl bg-sky-50/50 border border-sky-100 space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sky-800 font-medium">Translok Bulan Ini:</span>
                                                <span class="font-bold text-sky-900">
                                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(pet.workload.total_translok || 0)"></span>
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-sky-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-sky-500 rounded-full transition-all duration-300" :style="'width: ' + Math.min(100, Number(pet.workload.persentase_translok || 0)) + '%'"></div>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-sky-700/70">
                                                <span>Akumulasi SPJ Turun Lapangan</span>
                                                <span class="font-bold text-sky-800" x-text="(pet.workload.persentase_translok || 0) + '% dari acuan'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="button" @click="selectedEvent = null" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition shadow-2xs">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
