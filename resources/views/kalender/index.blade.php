@extends('layouts.app')

@section('title', 'Kalender Pekerjaan')

@section('content')
<div x-data="{ selectedEvent: null }" class="space-y-5">
    <!-- Header & Month Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Kalender Pekerjaan</h1>
            <p class="text-xs text-slate-500">
                @if($isManager)
                    Menampilkan jadwal turun lapangan dan deadline seluruh pegawai &amp; mitra yang ditugaskan.
                @else
                    Menampilkan jadwal turun lapangan dan deadline kegiatan Anda sendiri.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $prevBulan])) }}" class="p-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600" title="Bulan Sebelumnya">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <span class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 min-w-[140px] text-center">
                {{ $currentMonth->translatedFormat('F Y') }}
            </span>
            <a href="{{ route('kalender.index', array_merge(request()->query(), ['bulan' => $nextBulan])) }}" class="p-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600" title="Bulan Berikutnya">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('kalender.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 flex flex-wrap items-center gap-3">
        <input type="month" name="bulan" value="{{ $bulan }}" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">

        @if($isManager)
            <select name="tim_kerja_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <option value="">Semua Tim Kerja</option>
                @foreach($timKerjas as $tim)
                    <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                @endforeach
            </select>

            <select name="user_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <option value="">Semua Petugas</option>
                @foreach($usersList as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->tipe) }})</option>
                @endforeach
            </select>

            <select name="kecamatan_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
                @endforeach
            </select>
        @endif

        <select name="jenis_tahapan" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Jenis Tahapan</option>
            @foreach(['Teknis', 'Lapangan', 'Administrasi'] as $j)
                <option value="{{ $j }}" {{ request('jenis_tahapan') === $j ? 'selected' : '' }}>{{ $j }}</option>
            @endforeach
        </select>

        <select name="status" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Status</option>
            @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('kalender.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Legenda -->
    <div class="bg-white border border-slate-200/80 rounded-2xl px-4 py-3 flex flex-wrap items-center gap-3 text-[11px]">
        <span class="font-bold text-slate-700 mr-1">Legenda:</span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Turun Lapangan (Transport)
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 border border-sky-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-sky-500"></span> Deadline Teknis
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Deadline Lapangan
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-50 text-violet-800 border border-violet-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-violet-500"></span> Deadline Administrasi
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Overdue
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 font-medium">
            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Selesai
        </span>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[780px]">
                <!-- Day Names -->
                <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center text-[11px] font-bold text-slate-500 uppercase">
                    <div class="py-2.5">Senin</div>
                    <div class="py-2.5">Selasa</div>
                    <div class="py-2.5">Rabu</div>
                    <div class="py-2.5">Kamis</div>
                    <div class="py-2.5">Jumat</div>
                    <div class="py-2.5 text-amber-600">Sabtu</div>
                    <div class="py-2.5 text-rose-600">Minggu</div>
                </div>

                <!-- Days -->
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
                    @foreach($days as $day)
                        <div class="min-h-[120px] p-2 flex flex-col gap-1.5 {{ $day['in_month'] ? 'bg-white' : 'bg-slate-50/60 text-slate-400' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold {{ $day['is_today'] ? 'w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-400') }}">
                                    {{ $day['day'] }}
                                </span>
                                @if(count($day['events']) > 0)
                                    <span class="text-[10px] font-semibold text-slate-400">{{ count($day['events']) }}</span>
                                @endif
                            </div>

                            <div class="space-y-1 overflow-y-auto max-h-28 pr-0.5">
                                @foreach($day['events'] as $ev)
                                    <button
                                        type="button"
                                        @click="selectedEvent = {{ json_encode($ev) }}"
                                        class="w-full text-left px-2 py-1 rounded-md border text-[10px] font-semibold leading-tight truncate cursor-pointer hover:opacity-85 transition {{ $ev['color'] }}"
                                        title="{{ $ev['title'] }}"
                                    >
                                        {{ $ev['title'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Event -->
    <div x-show="selectedEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="selectedEvent = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="selectedEvent">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700" x-text="selectedEvent.jenis"></span>
                    <h3 class="text-sm font-bold text-slate-800 mt-1.5" x-text="selectedEvent.sub_kegiatan"></h3>
                </div>
                <button @click="selectedEvent = null" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <div class="space-y-2 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <p><strong class="text-slate-800">Petugas:</strong> <span x-text="selectedEvent.petugas"></span></p>
                <p><strong class="text-slate-800">Kegiatan:</strong> <span x-text="selectedEvent.kegiatan"></span> (<span x-text="selectedEvent.periode"></span>)</p>
                <p><strong class="text-slate-800">Wilayah:</strong> Kec. <span x-text="selectedEvent.kecamatan"></span></p>
                <p><strong class="text-slate-800">Tanggal:</strong> <span x-text="selectedEvent.tanggal"></span></p>
                <p><strong class="text-slate-800">Status:</strong> <span x-text="selectedEvent.status"></span></p>
                <p><strong class="text-slate-800">Keterangan:</strong> <span x-text="selectedEvent.info"></span></p>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="selectedEvent = null" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
