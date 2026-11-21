@extends('layouts.app')

@section('title', 'Matriks Alokasi Petugas')

@section('content')
<div x-data="{
    searchPetugas: '',
    hoveredRow: null,
    hoveredCol: null,
    cellModal: null,

    matchesSearch(name, identitas) {
        if (!this.searchPetugas.trim()) return true;
        const kw = this.searchPetugas.toLowerCase();
        return name.toLowerCase().includes(kw) || identitas.toLowerCase().includes(kw);
    }
}" class="space-y-6">

    <!-- Header Pastel & Lega -->
    <div class="bg-gradient-to-br from-indigo-50/60 via-white to-emerald-50/40 border border-slate-200/80 rounded-3xl p-6 sm:p-7 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/90 border border-indigo-100 text-[11px] font-bold text-indigo-700 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>Monitoring Lintas Kegiatan &amp; Tahapan</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">Matriks Alokasi Petugas</h1>
            <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
                Distribusi alokasi Translok dan Honor setiap petugas hingga detail tahapan pada rentang
                <strong>
                    @if($bulanDari->format('Y-m') === $bulanSampai->format('Y-m'))
                        {{ $bulanDari->translatedFormat('F Y') }}
                    @else
                        {{ $bulanDari->translatedFormat('F Y') }} &ndash; {{ $bulanSampai->translatedFormat('F Y') }}
                    @endif
                </strong>.
            </p>
        </div>

        <!-- Ringkasan Cepat Pastel -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 shrink-0">
            <div class="bg-white/90 backdrop-blur-xs border border-slate-200/70 rounded-2xl px-4 py-3 shadow-2xs transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Petugas / Periode</span>
                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ $petugasList->count() }} <span class="text-slate-300 font-normal">/</span> {{ $kegiatans->count() }}</p>
            </div>
            <div class="bg-sky-50/70 border border-sky-100 rounded-2xl px-4 py-3 shadow-2xs transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-[10px] font-bold text-sky-700/80 uppercase tracking-wider block">Total Translok</span>
                <p class="text-sm font-bold text-sky-900 mt-0.5">Rp {{ number_format($grandTotal['translok'], 0, ',', '.') }}</p>
            </div>
            <div class="col-span-2 sm:col-span-1 bg-emerald-50/70 border border-emerald-100 rounded-2xl px-4 py-3 shadow-2xs transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-[10px] font-bold text-emerald-700/80 uppercase tracking-wider block">Total Honor</span>
                <p class="text-sm font-bold text-emerald-900 mt-0.5">Rp {{ number_format($grandTotal['honor'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Panel Kontrol: Tab Tipe Petugas, Tab Jenis Alokasi, Rentang Bulan -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
            <!-- Baris Tab 1: Semua | Organik | Mitra -->
            <div class="lg:col-span-3 space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1 block">Kategori Petugas</span>
                <div class="inline-flex w-full p-1 bg-slate-100/80 rounded-2xl border border-slate-200/60">
                    @foreach(['semua' => 'Semua', 'organik' => 'Organik', 'mitra' => 'Mitra'] as $val => $label)
                        <a
                            href="{{ route('matriks.alokasi.index', ['tipe' => $val, 'jenis' => $jenis, 'bulan_dari' => $bulanDari->format('Y-m'), 'bulan_sampai' => $bulanSampai->format('Y-m')]) }}"
                            class="flex-1 text-center px-3 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $tipe === $val ? 'bg-white text-slate-800 shadow-xs ring-1 ring-slate-200/70' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Baris Tab 2: Semua | Translok | Tab Honor -->
            <div class="lg:col-span-3 space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1 block">Jenis Alokasi</span>
                <div class="inline-flex w-full p-1 bg-slate-100/80 rounded-2xl border border-slate-200/60">
                    @foreach(['semua' => 'Semua', 'translok' => 'Translok', 'honor' => 'Tab Honor'] as $val => $label)
                        <a
                            href="{{ route('matriks.alokasi.index', ['tipe' => $tipe, 'jenis' => $val, 'bulan_dari' => $bulanDari->format('Y-m'), 'bulan_sampai' => $bulanSampai->format('Y-m')]) }}"
                            class="flex-1 text-center px-3 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $jenis === $val ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-indigo-100' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Baris 3: Rentang Bulan (Dari - Sampai) & Cari Petugas -->
            <div class="lg:col-span-6 space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1 block">Rentang Bulan &amp; Cari Petugas</span>
                <div class="flex flex-col sm:flex-row gap-2">
                    <form method="GET" action="{{ route('matriks.alokasi.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-1.5 shrink-0">
                        <input type="hidden" name="tipe" value="{{ $tipe }}">
                        <input type="hidden" name="jenis" value="{{ $jenis }}">
                        <select
                            name="bulan_dari"
                            onchange="this.form.submit()"
                            class="text-xs font-bold text-slate-700 bg-indigo-50/50 border border-indigo-200/70 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-200 cursor-pointer transition"
                            title="Dari Bulan"
                        >
                            @foreach($bulanOptions as $opt)
                                <option value="{{ $opt['value'] }}" {{ $bulanDari->format('Y-m') === $opt['value'] ? 'selected' : '' }}>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-xs font-bold text-slate-400 px-0.5">s/d</span>
                        <select
                            name="bulan_sampai"
                            onchange="this.form.submit()"
                            class="text-xs font-bold text-slate-700 bg-indigo-50/50 border border-indigo-200/70 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-200 cursor-pointer transition"
                            title="Sampai Bulan"
                        >
                            @foreach($bulanOptions as $opt)
                                <option value="{{ $opt['value'] }}" {{ $bulanSampai->format('Y-m') === $opt['value'] ? 'selected' : '' }}>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    <div class="relative flex-1 min-w-[160px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-3"></i>
                        <input
                            type="text"
                            x-model="searchPetugas"
                            placeholder="Cari nama petugas..."
                            class="w-full text-xs bg-slate-50/80 border border-slate-200 rounded-xl pl-8 pr-3 py-2.5 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-200 transition"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Matriks Alokasi Petugas -->
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-2xs overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/60 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-100/70 text-indigo-700 flex items-center justify-center">
                    <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Matriks Alokasi Petugas</h2>
                    <p class="text-[11px] text-slate-400">Menampilkan rincian hingga tahapan di setiap sel. Klik sel untuk detail volume &amp; tanggal tugas.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-100">
                    <span class="w-2 h-2 rounded-full bg-sky-400"></span> Translok
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Honor
                </span>
            </div>
        </div>

        @if($petugasList->isEmpty() || $kegiatans->isEmpty())
            <div class="py-16 px-6 text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <i data-lucide="calendar-x" class="w-6 h-6"></i>
                </div>
                <p class="text-sm font-bold text-slate-700">Belum Ada Data Alokasi</p>
                <p class="text-xs text-slate-400 max-w-md mx-auto">
                    Tidak ditemukan alokasi {{ $jenis === 'semua' ? 'Translok/Honor' : ucfirst($jenis) }} untuk kategori {{ ucfirst($tipe) }} pada rentang bulan terpilih.
                </p>
            </div>
        @else
            <div class="overflow-x-auto max-h-[650px] relative">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur-xs border-b border-slate-200">
                        <tr>
                            <th class="sticky left-0 z-30 bg-slate-50/95 backdrop-blur-xs py-3.5 px-5 min-w-[220px] border-r border-slate-200/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                Nama Petugas
                            </th>
                            @foreach($kegiatans as $keg)
                                <th
                                    class="py-3 px-4 min-w-[205px] text-center border-r border-slate-100 transition-colors duration-150 align-top"
                                    :class="hoveredCol === {{ $keg['id'] }} ? 'bg-indigo-50/80 text-indigo-800' : 'text-slate-700'"
                                >
                                    <span class="block font-bold text-xs leading-snug">{{ $keg['nama_kegiatan'] }}</span>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50/80 text-indigo-700 border border-indigo-100">
                                        {{ $keg['nama_periode'] }}
                                    </span>
                                </th>
                            @endforeach
                            <th class="py-3.5 px-5 min-w-[150px] text-right bg-slate-100/70 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                Total Petugas
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($petugasList as $pet)
                            @php
                                $uid = $pet['id'];
                                $rTotal = $rowTotals[$uid];
                            @endphp
                            <tr
                                x-show="matchesSearch(@js($pet['name']), @js($pet['identitas']))"
                                x-transition.opacity.duration.150ms
                                class="transition-colors duration-150"
                                :class="hoveredRow === {{ $uid }} ? 'bg-indigo-50/30' : 'hover:bg-slate-50/50'"
                            >
                                <!-- Kolom Nama Petugas (Sticky Left) -->
                                <td
                                    class="sticky left-0 z-10 py-3 px-5 border-r border-slate-200/80 transition-colors duration-150 align-top"
                                    :class="hoveredRow === {{ $uid }} ? 'bg-indigo-50/90' : 'bg-white'"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full shrink-0 {{ $pet['tipe'] === 'mitra' ? 'bg-sky-400' : 'bg-emerald-400' }}"></span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-800 truncate max-w-[180px]" title="{{ $pet['name'] }}">{{ $pet['name'] }}</p>
                                            <p class="text-[10px] text-slate-400 truncate">
                                                {{ ucfirst($pet['tipe']) }} &bull; Kec. {{ $pet['kecamatan'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Sel Per Kegiatan+Periode (Dengan Detail Sampai Tahapan) -->
                                @foreach($kegiatans as $keg)
                                    @php
                                        $kid = $keg['id'];
                                        $cell = $matrix[$uid][$kid] ?? null;
                                    @endphp
                                    <td
                                        @mouseenter="hoveredRow = {{ $uid }}; hoveredCol = {{ $kid }}"
                                        @mouseleave="hoveredRow = null; hoveredCol = null"
                                        class="py-2.5 px-2.5 text-left border-r border-slate-100 align-top transition-colors duration-150"
                                        :class="(hoveredRow === {{ $uid }} && hoveredCol === {{ $kid }}) ? 'bg-indigo-50/60' : ((hoveredRow === {{ $uid }} || hoveredCol === {{ $kid }}) ? 'bg-slate-50/50' : '')"
                                    >
                                        @if($cell)
                                            @php
                                                $cellTotal = $jenis === 'translok' ? $cell['translok'] : ($jenis === 'honor' ? $cell['honor'] : ($cell['honor'] + $cell['translok']));
                                                $modalPayload = [
                                                    'petugas' => $pet['name'],
                                                    'tipe' => ucfirst($pet['tipe']),
                                                    'kegiatan' => $keg['nama_kegiatan'],
                                                    'periode' => $keg['nama_periode'],
                                                    'honor' => $cell['honor'],
                                                    'translok' => $cell['translok'],
                                                    'hari_translok' => $cell['hari_translok'],
                                                    'details' => $cell['details'],
                                                ];
                                            @endphp
                                            <button
                                                type="button"
                                                @click="cellModal = @js($modalPayload)"
                                                class="w-full text-left p-2.5 rounded-2xl border transition-all duration-200 hover:scale-[1.02] hover:shadow-xs cursor-pointer space-y-2 {{ $jenis === 'translok' ? 'bg-sky-50/70 border-sky-100 hover:border-sky-300' : ($jenis === 'honor' ? 'bg-emerald-50/70 border-emerald-100 hover:border-emerald-300' : 'bg-indigo-50/35 border-indigo-100/80 hover:border-indigo-300') }}"
                                            >
                                                <!-- Total Nominal Sel -->
                                                <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-200/60">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</span>
                                                    <span class="font-extrabold text-[11px] {{ $jenis === 'translok' ? 'text-sky-900' : ($jenis === 'honor' ? 'text-emerald-900' : 'text-slate-800') }}">
                                                        Rp {{ number_format($cellTotal, 0, ',', '.') }}
                                                    </span>
                                                </div>

                                                <!-- Detail Rincian per Tahapan -->
                                                <div class="space-y-1.5">
                                                    @foreach($cell['tahapans'] as $tahap)
                                                        @php
                                                            $tNominal = $jenis === 'translok' ? $tahap['translok'] : ($jenis === 'honor' ? $tahap['honor'] : ($tahap['honor'] + $tahap['translok']));
                                                        @endphp
                                                        <div class="bg-white/80 rounded-xl px-2 py-1.5 border border-slate-200/50 space-y-0.5">
                                                            <div class="flex items-center justify-between gap-1.5">
                                                                <span class="text-[10px] font-bold text-slate-700 truncate max-w-[105px]" title="{{ $tahap['nama'] }}">
                                                                    {{ $tahap['nama'] }}
                                                                </span>
                                                                <span class="text-[10px] font-bold text-slate-800 shrink-0">
                                                                    Rp {{ number_format($tNominal, 0, ',', '.') }}
                                                                </span>
                                                            </div>
                                                            <div class="flex flex-wrap gap-1 text-[9px]">
                                                                @if(($jenis === 'semua' || $jenis === 'translok') && $tahap['translok'] > 0)
                                                                    <span class="px-1.5 py-0.2 rounded bg-sky-100/70 text-sky-800 font-semibold">
                                                                        T: {{ number_format($tahap['translok'], 0, ',', '.') }} ({{ $tahap['hari_translok'] }}h)
                                                                    </span>
                                                                @endif
                                                                @if(($jenis === 'semua' || $jenis === 'honor') && $tahap['honor'] > 0)
                                                                    <span class="px-1.5 py-0.2 rounded bg-emerald-100/70 text-emerald-800 font-semibold">
                                                                        H: {{ number_format($tahap['honor'], 0, ',', '.') }} ({{ $tahap['volume'] }})
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </button>
                                        @else
                                            <div class="py-3 text-center text-slate-300 text-xs">&ndash;</div>
                                        @endif
                                    </td>
                                @endforeach

                                <!-- Total Baris (Per Petugas) -->
                                <td class="py-3 px-5 text-right bg-slate-50/70 font-bold text-slate-800 whitespace-nowrap align-top">
                                    @php
                                        $displayRowTotal = $jenis === 'translok' ? $rTotal['translok'] : ($jenis === 'honor' ? $rTotal['honor'] : $rTotal['total']);
                                    @endphp
                                    Rp {{ number_format($displayRowTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <!-- Footer Total Per Kegiatan -->
                    <tfoot class="sticky bottom-0 z-20 bg-slate-100/95 backdrop-blur-xs border-t-2 border-slate-200">
                        <tr>
                            <td class="sticky left-0 z-30 bg-slate-100/95 py-3.5 px-5 border-r border-slate-200 font-bold text-slate-700 text-xs uppercase tracking-wider">
                                Total Per Kegiatan
                            </td>
                            @foreach($kegiatans as $keg)
                                @php
                                    $ct = $colTotals[$keg['id']];
                                    $displayColTotal = $jenis === 'translok' ? $ct['translok'] : ($jenis === 'honor' ? $ct['honor'] : $ct['total']);
                                @endphp
                                <td class="py-3.5 px-3 text-center border-r border-slate-200/60 font-bold text-slate-800 whitespace-nowrap">
                                    Rp {{ number_format($displayColTotal, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="py-3.5 px-5 text-right font-extrabold text-indigo-800 bg-indigo-50/90 whitespace-nowrap">
                                @php
                                    $displayGrand = $jenis === 'translok' ? $grandTotal['translok'] : ($jenis === 'honor' ? $grandTotal['honor'] : $grandTotal['total']);
                                @endphp
                                Rp {{ number_format($displayGrand, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL RINCIAN SEL MATRIKS (ANIMASI HALUS & PASTEL) -->
    <div
        x-show="cellModal"
        x-cloak
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6"
    >
        <div
            @click.away="cellModal = null"
            x-show="cellModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100 max-h-[88vh] overflow-y-auto"
            x-if="cellModal"
        >
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100" x-text="cellModal.kegiatan"></span>
                        <span class="inline-block px-2.5 py-1 text-[10px] font-semibold rounded-lg bg-slate-100 text-slate-600" x-text="cellModal.periode"></span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1.5" x-text="cellModal.petugas"></h3>
                    <p class="text-xs text-slate-400" x-text="cellModal.tipe"></p>
                </div>
                <button @click="cellModal = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3.5 rounded-2xl bg-sky-50/70 border border-sky-100">
                    <span class="text-[10px] font-bold text-sky-700 uppercase block">Total Translok (<span x-text="cellModal.hari_translok"></span> hari)</span>
                    <p class="text-sm font-bold text-sky-900 mt-0.5">Rp <span x-text="new Intl.NumberFormat('id-ID').format(cellModal.translok)"></span></p>
                </div>
                <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-100">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase block">Total Honor</span>
                    <p class="text-sm font-bold text-emerald-900 mt-0.5">Rp <span x-text="new Intl.NumberFormat('id-ID').format(cellModal.honor)"></span></p>
                </div>
            </div>

            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3.5">Jenis</th>
                            <th class="py-2.5 px-3.5">Tahapan &amp; Keterangan</th>
                            <th class="py-2.5 px-3.5 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(d, i) in cellModal.details" :key="i">
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-2.5 px-3.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="d.kategori === 'Translok' ? 'bg-sky-50 text-sky-700 border border-sky-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'" x-text="d.kategori"></span>
                                </td>
                                <td class="py-2.5 px-3.5">
                                    <p class="font-bold text-slate-800" x-text="d.tahapan"></p>
                                    <p class="text-[10px] text-slate-400" x-text="d.periode + ' • ' + d.info"></p>
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-slate-800">
                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(d.nominal)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" @click="cellModal = null" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
