@extends('layouts.app')

@section('title', 'Distribusi Penugasan: ' . $sub->nama)

@section('content')
<div x-data="penugasanWizard()" class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('kegiatan.periode.show', $periode->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition mb-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Periode Kegiatan</span>
            </a>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Distribusi Petugas: {{ $sub->nama }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ $periode->masterKegiatan?->nama }} &bull; {{ $periode->nama_periode }} &bull; Acuan Mitra KEPKA Tahun <strong>{{ $tahun }}</strong>
            </p>
        </div>

        <!-- Stepper Indicator (Lega, Rapi, Pastel) -->
        <div class="flex items-center gap-1.5 sm:gap-2 self-start sm:self-auto bg-white p-2 rounded-2xl border border-slate-200/80 shadow-2xs overflow-x-auto max-w-full">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0" :class="step === 1 ? 'bg-slate-800 text-white' : (step > 1 ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400')">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 1 ? 'bg-white/20 text-white' : (step > 1 ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-100 text-slate-500')">1</span>
                <span>Metode</span>
            </div>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0" :class="step === 2 ? 'bg-slate-800 text-white' : (step > 2 ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400')">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 2 ? 'bg-white/20 text-white' : (step > 2 ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-100 text-slate-500')">2</span>
                <span>Petugas</span>
            </div>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0" :class="step === 3 ? 'bg-slate-800 text-white' : (step > 3 ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400')">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 3 ? 'bg-white/20 text-white' : (step > 3 ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-100 text-slate-500')">3</span>
                <span>Wilayah &amp; Beban</span>
            </div>
            <template x-if="adaPengawas">
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0" :class="step === 4 ? 'bg-slate-800 text-white' : (step > 4 ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400')">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 4 ? 'bg-white/20 text-white' : (step > 4 ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-100 text-slate-500')">4</span>
                        <span>Pengawas</span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Banner Info Kuota Jam & SBML Bulan Ini -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bulan Pelaksanaan</span>
                <p class="text-base font-bold text-slate-800">{{ $infoBulan['nama_bulan'] }} ({{ $infoBulan['hari_kerja'] }} HK)</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs">HK</div>
        </div>
        <div class="bg-gradient-to-br from-sky-50/50 to-white border border-sky-100 rounded-2xl p-4 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-sky-700/70 uppercase tracking-wider">Kuota Jam Kerja Petugas</span>
                <p class="text-base font-bold text-sky-800">{{ $infoBulan['kuota_jam'] }} Jam <span class="text-xs font-normal text-sky-600">({{ $infoBulan['hari_kerja'] }} &times; 8 jam)</span></p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-sky-100/70 text-sky-700 flex items-center justify-center">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-50/50 to-white border border-emerald-100 rounded-2xl p-4 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-700/70 uppercase tracking-wider">Batas SBML Mitra</span>
                <p class="text-base font-bold text-emerald-800">Rp {{ number_format($infoBulan['sbml'], 0, ',', '.') }} <span class="text-xs font-normal text-emerald-600">/ bulan</span></p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HALAMAN 1: Menentukan Apakah Input Manual atau Pakai File Template Excel -->
    <!-- ========================================================================= -->
    <div x-show="step === 1" class="space-y-6">
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-10 shadow-2xs space-y-8">
            <div class="text-center max-w-xl mx-auto space-y-2">
                <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Langkah 1</span>
                <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pilih Metode Distribusi Petugas</h2>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Anda dapat mendistribusikan tugas secara langsung melalui wizard interaktif, atau menggunakan template file Excel massal dengan validasi otomatis anti-double SPJ.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                <!-- Opsi A: Input Manual Bertahap -->
                <div
                    @click="metode = 'manual'"
                    class="p-6 rounded-3xl border-2 transition cursor-pointer flex flex-col justify-between space-y-4 shadow-2xs hover:border-slate-400"
                    :class="metode === 'manual' ? 'border-slate-800 bg-slate-50/60 ring-2 ring-slate-800/10' : 'border-slate-200 bg-white'"
                >
                    <div class="space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                            <i data-lucide="user-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Input Manual Interaktif</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Pilih pegawai organik &amp; mitra KEPKA tahun {{ $tahun }} dengan mencentang nama, atur level wilayah (5 level), serta alokasikan pengawas jika ada.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 pt-2 border-t border-slate-100">
                        <input type="radio" name="pilih_metode" value="manual" :checked="metode === 'manual'" class="w-4 h-4 text-slate-800">
                        <span>Gunakan Wizard Manual</span>
                    </div>
                </div>

                <!-- Opsi B: Pakai File Template Excel -->
                <div
                    @click="metode = 'excel'"
                    class="p-6 rounded-3xl border-2 transition cursor-pointer flex flex-col justify-between space-y-4 shadow-2xs hover:border-slate-400"
                    :class="metode === 'excel' ? 'border-emerald-600 bg-emerald-50/40 ring-2 ring-emerald-600/10' : 'border-slate-200 bg-white'"
                >
                    <div class="space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Import File Excel Template</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Distribusikan puluhan/ratusan penugasan sekaligus melalui berkas spreadsheet Excel (.xlsx). Sudah disediakan template baku siap pakai.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 pt-2 border-t border-emerald-100">
                        <input type="radio" name="pilih_metode" value="excel" :checked="metode === 'excel'" class="w-4 h-4 text-emerald-600">
                        <span>Gunakan Import Excel</span>
                    </div>
                </div>
            </div>

            <!-- Jika memilih Excel: Tampilkan Bagian Download Template & Form Upload -->
            <div x-show="metode === 'excel'" class="max-w-3xl mx-auto p-6 bg-slate-50/80 rounded-3xl border border-slate-200 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Template File Excel Penugasan</h4>
                        <p class="text-xs text-slate-500">Download template resmi untuk tahapan ini, isi data petugas, lalu upload kembali di bawah.</p>
                    </div>
                    <a href="{{ route('penugasan.wizard.template', $sub) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition shadow-2xs shrink-0">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download Template (.xlsx)</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('penugasan.import', $periode) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pilih Berkas Excel Hasil Pengisian (.xlsx / .csv)</label>
                        <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-3 bg-white file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-700 text-white font-semibold hover:bg-emerald-800 transition shadow-2xs cursor-pointer">
                            Upload &amp; Jalankan Distribusi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Navigasi Langkah Berikutnya (Untuk Metode Manual) -->
            <div x-show="metode === 'manual'" class="flex justify-end pt-4 border-t border-slate-100">
                <button type="button" @click="step = 2" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer text-xs">
                    <span>Lanjut ke Langkah 2: Pilih Petugas</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HALAMAN 2: Menampilkan Daftar Pegawai Organik & Mitra, Centang + Search -->
    <!-- ========================================================================= -->
    <div x-show="step === 2" class="space-y-6">
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-2xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Langkah 2</span>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight mt-1">Pilih Pegawai Organik &amp; Mitra Terlibat</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Centang petugas yang akan ditugaskan. Tersedia filter pencarian dan opsi pilih semua.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl">
                        Terpilih: <strong class="text-slate-900" x-text="selectedPetugasIds.length"></strong> Petugas
                    </span>
                </div>
            </div>

            <!-- Filter Pencarian & Tipe Petugas Server-Side (Yajra-Style) -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[240px] relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                    <input
                        type="text"
                        x-model="searchKeyword"
                        @input.debounce.300ms="fetchPetugas(1)"
                        placeholder="Cari nama pegawai, NIP, ID SOBAT, kecamatan..."
                        class="w-full text-xs bg-slate-50/80 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"
                    >
                </div>
                <select
                    x-model="filterTipe"
                    @change="fetchPetugas(1)"
                    class="text-xs bg-slate-50/80 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"
                >
                    <option value="all">Semua Tipe (Organik &amp; Mitra)</option>
                    <option value="organik">Hanya Pegawai Organik</option>
                    <option value="mitra">Hanya Mitra KEPKA (Tahun {{ $tahun }})</option>
                </select>
                <select
                    x-model.number="perPage"
                    @change="fetchPetugas(1)"
                    class="text-xs bg-slate-50/80 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-semibold"
                >
                    <option value="15">15 per hal</option>
                    <option value="30">30 per hal</option>
                    <option value="50">50 per hal</option>
                    <option value="100">100 per hal</option>
                </select>
                <button
                    type="button"
                    @click="toggleSelectAllPage()"
                    class="px-4 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                >
                    <span x-text="isAllPageSelected() ? 'Batal Pilih Halaman Ini' : 'Centang Halaman Ini'"></span>
                </button>
            </div>

            <!-- Tabel Daftar Petugas untuk Dicentang (Server-side Yajra Style) -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs relative">
                <!-- Loading State Indicator -->
                <div x-show="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-2xs flex items-center justify-center z-20">
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 bg-white px-4 py-2 rounded-xl shadow-md border border-slate-100">
                        <svg class="animate-spin h-4 w-4 text-slate-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memuat data petugas...</span>
                    </div>
                </div>

                <div class="max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider z-10">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">Pilih</th>
                                <th class="py-3 px-4">Nama Petugas &amp; Identitas</th>
                                <th class="py-3 px-4">Tipe / Role</th>
                                <th class="py-3 px-4">Wilayah Domisili</th>
                                <th class="py-3 px-4">Beban Jam Kerja Aktif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="p in petugasList" :key="p.id">
                                <tr
                                    @click="togglePetugas(p)"
                                    class="cursor-pointer transition-colors"
                                    :class="selectedPetugasIds.some(id => id == p.id) ? 'bg-indigo-50/50 hover:bg-indigo-50/70' : 'hover:bg-slate-50/60'"
                                >
                                    <td class="py-3 px-4 text-center" @click.stop>
                                        <input
                                            type="checkbox"
                                            :value="p.id"
                                            :checked="selectedPetugasIds.some(id => id == p.id)"
                                            @change="togglePetugas(p)"
                                            class="w-4 h-4 rounded border-slate-300 text-slate-800 focus:ring-slate-400 cursor-pointer"
                                        >
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full" :class="p.tipe === 'mitra' ? 'bg-sky-500' : 'bg-emerald-500'"></span>
                                            <span x-text="p.name"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            ID/NIP: <span class="font-mono text-slate-600 font-semibold" x-text="p.nip_sobat"></span>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="px-2.5 py-0.5 rounded-md text-[10px] font-bold capitalize"
                                            :class="p.tipe === 'mitra' ? 'bg-sky-50 text-sky-700 border border-sky-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
                                            x-text="p.tipe"
                                        ></span>
                                        <p class="text-[11px] text-slate-400 mt-1" x-text="p.role_label"></p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-semibold text-slate-700">Kec. <span x-text="p.kecamatan_nama"></span></p>
                                        <p class="text-[11px] text-slate-400" x-text="p.tim"></p>
                                    </td>
                                    <td class="py-3 px-4 min-w-[150px]">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-bold text-slate-700"><span x-text="p.workload.total_jam"></span> / <span x-text="p.workload.kuota_jam"></span> Jam</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="p.workload.badge_class" x-text="p.workload.status"></span>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full" :class="p.workload.bar_class" :style="'width: ' + Math.min(100, p.workload.persentase_jam) + '%'"></div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!isLoading && petugasList.length === 0">
                                <td colspan="5" class="py-10 text-center text-slate-400 italic">
                                    Tidak ada data petugas yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar Server-side Yajra Style -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                    <div>
                        Menampilkan <strong x-text="((currentPage - 1) * perPage) + (petugasList.length > 0 ? 1 : 0)"></strong> sampai <strong x-text="((currentPage - 1) * perPage) + petugasList.length"></strong> dari <strong x-text="totalRecords"></strong> total petugas
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="fetchPetugas(currentPage - 1)"
                            :disabled="currentPage <= 1 || isLoading"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            &larr; Prev
                        </button>
                        <span class="px-3 py-1.5 font-bold text-slate-800">
                            Hal <span x-text="currentPage"></span> / <span x-text="lastPage || 1"></span>
                        </span>
                        <button
                            type="button"
                            @click="fetchPetugas(currentPage + 1)"
                            :disabled="currentPage >= lastPage || isLoading"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-semibold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigasi Langkah 2 -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" @click="step = 1" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold text-xs">
                    &larr; Kembali
                </button>
                <button
                    type="button"
                    @click="proceedToStep3()"
                    :disabled="selectedPetugasIds.length === 0"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer text-xs disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <span>Lanjut ke Langkah 3: Wilayah &amp; Beban (<span x-text="selectedPetugasIds.length"></span>)</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FORM UTAMA UNTUK LANGKAH 3 (WILAYAH & BEBAN) DAN LANGKAH 4 (PENGAWAS)    -->
    <!-- ========================================================================= -->
    <form method="POST" action="{{ route('penugasan.wizard.store', $sub) }}" class="space-y-6">
        @csrf
        <input type="hidden" name="level_wilayah" :value="levelWilayah">
        <input type="hidden" name="ada_pengawas" :value="adaPengawas ? 1 : 0">

        <!-- ===================================================================== -->
        <!-- HALAMAN 3: Penentuan Level Wilayah, Pengisian Wilayah (>1) & Beban   -->
        <!-- ===================================================================== -->
        <div x-show="step === 3" class="space-y-6">
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-2xs space-y-6">
                <!-- Header Langkah 3 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Langkah 3</span>
                        <h2 class="text-xl font-bold text-slate-800 tracking-tight mt-1">Atur Wilayah Tugas &amp; Beban Kerja</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Tentukan level wilayah penugasan (Kecamatan, Desa, SLS, Sub-SLS, atau NKS), isi wilayah tugas (bisa &gt;1), dan alokasikan target volume.</p>
                    </div>

                    <!-- Quick Bulk Settings -->
                    <div class="flex flex-wrap items-center gap-2 bg-slate-50 p-2.5 rounded-2xl border border-slate-200/80 text-xs">
                        <span class="font-bold text-slate-600 text-[11px]">Set Serentak:</span>
                        <input type="number" x-model="bulkVolume" placeholder="Vol" class="w-16 bg-white border border-slate-200 rounded-lg px-2 py-1 text-center font-bold">
                        <button type="button" @click="applyBulkVolume()" class="px-2.5 py-1 bg-slate-800 text-white font-semibold rounded-lg hover:bg-slate-900 transition cursor-pointer">Terapkan Vol</button>
                    </div>
                </div>

                <!-- KOTAK PILIHAN LEVEL WILAYAH (5 PILIHAN DITANYAKAN TERLEBIH DAHULU) -->
                <div class="p-5 rounded-3xl bg-gradient-to-br from-indigo-50/40 via-white to-slate-50/60 border border-indigo-100 shadow-2xs space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Konfigurasi Wilayah Tugas</span>
                            <h3 class="text-sm font-bold text-slate-800">Pilih Level Wilayah Penugasan untuk Tahapan Ini:</h3>
                        </div>
                        <span class="text-xs text-slate-500">
                            Level aktif: <strong class="text-indigo-700 capitalize" x-text="levelWilayahLabel(levelWilayah)"></strong>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-1">
                        <template x-for="lvl in levelOptions" :key="lvl.id">
                            <button
                                type="button"
                                @click="setLevelWilayah(lvl.id)"
                                class="p-3 rounded-2xl border-2 text-left transition flex flex-col justify-between cursor-pointer space-y-1.5"
                                :class="levelWilayah === lvl.id 
                                    ? 'border-indigo-600 bg-indigo-50/80 shadow-2xs ring-2 ring-indigo-600/10' 
                                    : 'border-slate-200/80 bg-white hover:border-slate-300'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800" x-text="lvl.label"></span>
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600" x-show="levelWilayah === lvl.id"></i>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-tight" x-text="lvl.desc"></p>
                            </button>
                        </template>
                    </div>

                    <!-- Toggle Role Pengawas Lapangan -->
                    <div class="pt-3 border-t border-indigo-100/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700">
                            <input
                                type="checkbox"
                                x-model="adaPengawas"
                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-400"
                            >
                            <span>Apakah tahapan ini melibatkan <strong>Role Pengawas (PML/Supervisor)</strong>?</span>
                        </label>
                        <span class="text-[11px]" :class="adaPengawas ? 'text-indigo-700 font-bold' : 'text-slate-400'">
                            <span x-show="adaPengawas">&bull; Lanjut ke Langkah 4 untuk Alokasi Pengawas</span>
                            <span x-show="!adaPengawas">&bull; Tanpa Pengawas (Langsung Simpan di Langkah 3)</span>
                        </span>
                    </div>
                </div>

                <!-- Progress Bar Pengisian Tugas Petugas -->
                <div class="p-4 rounded-2xl border transition shadow-2xs space-y-2.5"
                     :class="completedAssignmentsCount() === assignments.length ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50/80 border-slate-200'">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-bold transition"
                                  :class="completedAssignmentsCount() === assignments.length ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-700'">
                                <i data-lucide="check" class="w-4 h-4" x-show="completedAssignmentsCount() === assignments.length"></i>
                                <span x-show="completedAssignmentsCount() !== assignments.length" x-text="completedAssignmentsCount()"></span>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">
                                    Progres Pengisian Data Penugasan Petugas
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    <strong class="text-slate-700" x-text="completedAssignmentsCount()"></strong> dari <strong class="text-slate-700" x-text="assignments.length"></strong> petugas telah siap didistribusikan (<span class="font-semibold text-slate-800" x-text="completionPercentage() + '%'"></span>).
                                    <span x-show="completedAssignmentsCount() < assignments.length" class="text-rose-600 font-medium">
                                        (Ada <span x-text="assignments.length - completedAssignmentsCount()"></span> petugas belum lengkap wilayah/target)
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-semibold bg-emerald-100/70 text-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Lengkap: <strong x-text="completedAssignmentsCount()"></strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-semibold bg-rose-100/70 text-rose-800" x-show="completedAssignmentsCount() < assignments.length">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Belum: <strong x-text="assignments.length - completedAssignmentsCount()"></strong>
                            </span>
                        </div>
                    </div>
                    <!-- Bar Visual -->
                    <div class="w-full h-2.5 bg-slate-200/80 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-300 ease-out"
                            :class="completedAssignmentsCount() === assignments.length ? 'bg-emerald-500' : 'bg-slate-700'"
                            :style="'width: ' + completionPercentage() + '%'"
                        ></div>
                    </div>
                </div>

                <!-- Info Default Satuan & Waktu -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Satuan Default:</span>
                        <p class="font-bold text-slate-800">{{ $sub->nama }} (Dokumen)</p>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Estimasi Waktu:</span>
                        <p class="font-bold text-slate-800">{{ (float) ($sub->estimasi_jam_per_satuan ?: 7) }} Jam / Satuan</p>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Honor / Satuan:</span>
                        <p class="font-bold text-slate-800">Rp {{ number_format($sub->honor_per_satuan ?: 0, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Deadline Tahapan:</span>
                        <p class="font-bold text-slate-800">{{ $sub->deadline_tahapan?->format('d M Y') ?? '-' }}</p>
                    </div>
                </div>

                <!-- DAFTAR KARTU PETUGAS TERPILIH (DEFAULT AWAL MERAH KARENA WILAYAH BELUM KEISI) -->
                <div class="space-y-4">
                    <template x-for="(assign, index) in assignments" :key="assign.user_id">
                        <div
                            class="p-5 rounded-2xl border transition space-y-4 shadow-2xs"
                            :class="isAssignmentComplete(assign) 
                                ? 'bg-emerald-50/40 border-emerald-200/80 hover:border-emerald-300' 
                                : 'bg-rose-50/50 border-rose-200/90 hover:border-rose-300'"
                        >
                            <input type="hidden" :name="'assignments[' + index + '][user_id]'" :value="assign.user_id">
                            <input type="hidden" :name="'assignments[' + index + '][satuan_volume]'" :value="assign.satuan_volume">
                            <input type="hidden" :name="'assignments[' + index + '][estimasi_jam_per_satuan]'" :value="assign.estimasi_jam_per_satuan">
                            <input type="hidden" :name="'assignments[' + index + '][honor_per_satuan]'" :value="assign.honor_per_satuan">

                            <!-- Baris 1: Identitas Petugas, Status Kelengkapan & Tombol Hapus -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="(assign.petugas?.tipe || 'organik') === 'mitra' ? 'bg-sky-500' : 'bg-emerald-500'"></span>
                                        <h3 class="text-base font-bold text-slate-800" x-text="assign.petugas?.name || ('Petugas ID #' + assign.user_id)"></h3>
                                        <span
                                            class="text-[10px] px-2 py-0.5 rounded-md font-bold capitalize"
                                            :class="(assign.petugas?.tipe || 'organik') === 'mitra' ? 'bg-sky-100 text-sky-800 border border-sky-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                                            x-text="assign.petugas?.tipe || 'petugas'"
                                        ></span>

                                        <!-- Badge Status Pengisian Pastel -->
                                        <template x-if="isAssignmentComplete(assign)">
                                            <span class="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600"></i>
                                                <span>Data Lengkap</span>
                                            </span>
                                        </template>
                                        <template x-if="!isAssignmentComplete(assign)">
                                            <span class="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600"></i>
                                                <span x-text="getIncompleteReason(assign)"></span>
                                            </span>
                                        </template>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        ID/NIP: <span class="font-mono text-slate-700 font-semibold" x-text="assign.petugas?.nip_sobat || '-'"></span> &bull; Domisili: Kec. <span class="text-slate-700 font-medium" x-text="assign.petugas?.kecamatan_nama || '-'"></span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeAssignment(index)"
                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-100/70 rounded-xl transition cursor-pointer"
                                    title="Hapus dari daftar penugasan ini"
                                >
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Baris 2: Kecamatan Induk & PENGISIAN WILAYAH TUGAS (BISA >1) SESUAI LEVEL -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Induk / Domisili</label>
                                    <select :name="'assignments[' + index + '][kecamatan_id]'" x-model="assign.kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                                        <option value="">- Pilih Kecamatan -</option>
                                        @foreach($kecamatans as $kec)
                                            <option value="{{ $kec->id }}">Kec. {{ $kec->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block font-semibold text-slate-700">
                                            Daftar Wilayah Tugas (<span class="capitalize text-indigo-700" x-text="levelWilayahLabel(levelWilayah)"></span>) <span class="text-rose-500">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="addWilayahItem(assign)"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition cursor-pointer"
                                        >
                                            <i data-lucide="plus" class="w-3 h-3"></i>
                                            <span>Tambah Wilayah</span>
                                        </button>
                                    </div>

                                    <!-- Render Items Wilayah (Bisa Lebih dari 1) dengan AUTOCOMPLETE BERJENJANG -->
                                    <div class="space-y-2.5">
                                        <template x-for="(wItem, wIdx) in assign.wilayah_tugas" :key="wIdx">
                                            <div class="relative" x-data="{
                                                searchQuery: assign.wilayah_tugas[wIdx],
                                                suggestions: [],
                                                isOpen: false,
                                                isLoading: false,
                                                debounceTimer: null,

                                                fetchSuggestions() {
                                                    clearTimeout(this.debounceTimer);
                                                    if (!this.searchQuery || this.searchQuery.length < 1) {
                                                        this.suggestions = [];
                                                        this.isOpen = false;
                                                        return;
                                                    }
                                                    this.isLoading = true;
                                                    this.debounceTimer = setTimeout(() => {
                                                        const url = '{{ route('api.wilayah.search') }}?level=' + levelWilayah + '&q=' + encodeURIComponent(this.searchQuery) + (assign.kecamatan_id ? '&kecamatan_id=' + assign.kecamatan_id : '');
                                                        fetch(url)
                                                            .then(res => res.json())
                                                            .then(data => {
                                                                this.suggestions = data;
                                                                this.isOpen = this.suggestions.length > 0;
                                                                this.isLoading = false;
                                                            })
                                                            .catch(() => {
                                                                this.isLoading = false;
                                                            });
                                                    }, 250);
                                                },

                                                selectItem(item) {
                                                    this.searchQuery = item.value;
                                                    assign.wilayah_tugas[wIdx] = item.value;
                                                    if (item.id_kec && !assign.kecamatan_id) {
                                                        assign.kecamatan_id = item.id_kec;
                                                    }
                                                    this.isOpen = false;
                                                }
                                            }">
                                                <div class="flex items-center gap-2">
                                                    <div class="relative flex-1">
                                                        <input
                                                            type="text"
                                                            :name="'assignments[' + index + '][wilayah_tugas][]'"
                                                            x-model="searchQuery"
                                                            @input="assign.wilayah_tugas[wIdx] = searchQuery; fetchSuggestions()"
                                                            @focus="fetchSuggestions()"
                                                            @click.outside="isOpen = false"
                                                            :placeholder="getWilayahPlaceholder(assign)"
                                                            class="w-full border rounded-xl pl-3.5 pr-8 py-2.5 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1"
                                                            :class="assign.wilayah_tugas[wIdx] && assign.wilayah_tugas[wIdx].trim() !== '' ? 'border-slate-200 focus:ring-slate-400' : 'border-rose-300 ring-1 ring-rose-200'"
                                                        >
                                                        <!-- Icon Loading / Search -->
                                                        <div class="absolute right-3 top-3 text-slate-400 pointer-events-none">
                                                            <svg x-show="isLoading" class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                            </svg>
                                                            <i data-lucide="search" class="w-3.5 h-3.5" x-show="!isLoading"></i>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="removeWilayahItem(assign, wIdx)"
                                                        class="p-2.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                                                        x-show="assign.wilayah_tugas.length > 1"
                                                        title="Hapus wilayah ini"
                                                    >
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN SUGGESTION AUTOCOMPLETE (BERJENJANG SESUAI LEVEL PREVIOUS) -->
                                                <div
                                                    x-show="isOpen"
                                                    x-cloak
                                                    class="absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-2xl border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100"
                                                >
                                                    <template x-for="(sug, sIdx) in suggestions" :key="sIdx">
                                                        <div
                                                            @click="selectItem(sug)"
                                                            class="px-3.5 py-2 hover:bg-indigo-50/70 cursor-pointer transition flex flex-col justify-between"
                                                        >
                                                            <span class="font-bold text-slate-800" x-text="sug.label"></span>
                                                            <span class="text-[10px] text-slate-400 mt-0.5" x-text="sug.subtext"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Petugas dapat dialokasikan lebih dari 1 wilayah tugas. Klik "+ Tambah Wilayah" untuk menambah cakupan wilayah lainnya.
                                    </p>
                                </div>
                            </div>

                            <!-- Baris 3: Target Volume, Deadline & Alokasi Transport -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-1">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1.5">
                                        Target Volume Beban (<span x-text="assign.satuan_volume"></span>) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        :name="'assignments[' + index + '][target_volume]'"
                                        x-model.number="assign.target_volume"
                                        min="1"
                                        placeholder="Min. 1"
                                        required
                                        class="w-full border rounded-xl px-3.5 py-2.5 bg-white font-bold text-slate-800 focus:outline-none focus:ring-1"
                                        :class="assign.target_volume > 0 ? 'border-slate-200 focus:ring-slate-400' : 'border-rose-300 ring-1 ring-rose-200'"
                                    >
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tugas Petugas</label>
                                    <input
                                        type="date"
                                        :name="'assignments[' + index + '][deadline_tugas]'"
                                        x-model="assign.deadline_tugas"
                                        class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"
                                    >
                                </div>
                            </div>

                            <!-- Baris 4: Opsi Alokasi Transportasi Lokal -->
                            <div class="p-4 rounded-2xl border text-xs space-y-3"
                                 :class="assign.dapat_transport ? 'bg-amber-50/50 border-amber-200/80' : 'bg-white/80 border-slate-200/80'">
                                <label class="inline-flex items-center gap-2.5 font-semibold text-slate-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :name="'assignments[' + index + '][dapat_transport]'"
                                        value="1"
                                        x-model="assign.dapat_transport"
                                        class="w-4 h-4 rounded border-slate-300 text-slate-800 focus:ring-slate-400"
                                    >
                                    <span>Alokasikan Transportasi Lokal (SPJ Lapangan)</span>
                                </label>
                                <div x-show="assign.dapat_transport" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">
                                            Tanggal Tugas Lapangan (YYYY-MM-DD, pisah koma) <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            :name="'assignments[' + index + '][tgl_tugas_transport]'"
                                            x-model="assign.tgl_tugas_transport"
                                            placeholder="2026-02-05, 2026-02-06"
                                            class="w-full border rounded-xl px-3.5 py-2 bg-white focus:outline-none focus:ring-1"
                                            :class="assign.tgl_tugas_transport && assign.tgl_tugas_transport.trim() !== '' ? 'border-slate-200 focus:ring-slate-400' : 'border-rose-300 ring-1 ring-rose-200'"
                                        >
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Nominal per Hari (Rp)</label>
                                        <input
                                            type="number"
                                            :name="'assignments[' + index + '][nominal_transport]'"
                                            x-model.number="assign.nominal_transport"
                                            min="0"
                                            class="w-full border border-slate-200 rounded-xl px-3.5 py-2 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Baris 5: INDIKATOR REAL-TIME JAM KERJA & SBML -->
                            <div class="pt-3 border-t border-slate-200/60 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <!-- Indikator Jam Kerja Real-time -->
                                <div class="p-3 rounded-xl bg-white/90 border border-slate-200/80 space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium text-[11px]">Beban Jam Kerja Setelah Ditambah:</span>
                                        <span class="font-bold text-xs" :class="calculateLiveJam(assign).is_aman ? 'text-emerald-700' : 'text-rose-600'">
                                            <span x-text="calculateLiveJam(assign).total"></span> / {{ $infoBulan['kuota_jam'] }} Jam
                                        </span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="calculateLiveJam(assign).is_aman ? (calculateLiveJam(assign).persen > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'"
                                            :style="'width: ' + Math.min(100, calculateLiveJam(assign).persen) + '%'"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] text-slate-400">
                                        <span>Status: <strong :class="calculateLiveJam(assign).is_aman ? 'text-slate-700' : 'text-rose-600'" x-text="calculateLiveJam(assign).is_aman ? 'Aman (≤ Kuota Jam)' : 'Overload Jam Kerja!'"></strong></span>
                                        <span class="font-bold text-slate-600" x-text="calculateLiveJam(assign).persen + '%'"></span>
                                    </div>
                                </div>

                                <!-- Indikator SBML Real-time (Khusus Mitra) -->
                                <div class="p-3 rounded-xl bg-white/90 border border-slate-200/80 space-y-1.5 shadow-2xs" x-show="(assign.petugas?.tipe || 'mitra') === 'mitra'">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium text-[11px]">Estimasi Honor Setelah Ditambah:</span>
                                        <span class="font-bold text-xs" :class="calculateLiveHonor(assign).is_aman ? 'text-emerald-700' : 'text-rose-600'">
                                            Rp <span x-text="new Intl.NumberFormat('id-ID').format(calculateLiveHonor(assign).total)"></span>
                                        </span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="calculateLiveHonor(assign).is_aman ? (calculateLiveHonor(assign).persen > 80 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-rose-500'"
                                            :style="'width: ' + Math.min(100, calculateLiveHonor(assign).persen) + '%'"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] text-slate-400">
                                        <span>Maks SBML: Rp {{ number_format($infoBulan['sbml'], 0, ',', '.') }}</span>
                                        <span class="font-bold" :class="calculateLiveHonor(assign).is_aman ? 'text-emerald-600' : 'text-rose-600'" x-text="calculateLiveHonor(assign).is_aman ? 'Aman (≤ SBML)' : 'Melebihi Batas SBML!'"></span>
                                    </div>
                                </div>

                                <!-- Keterangan Organik -->
                                <div class="p-3 rounded-xl bg-white/90 border border-slate-200/80 flex items-center justify-between shadow-2xs" x-show="(assign.petugas?.tipe || 'mitra') !== 'mitra'">
                                    <div>
                                        <span class="text-slate-600 font-medium block text-xs">Pegawai Organik BPS</span>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Penugasan kedinasan dihitung berdasarkan jam kerja.</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border" :class="calculateLiveJam(assign).is_aman ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" x-text="calculateLiveJam(assign).is_aman ? 'Aman' : 'Overload'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Navigasi Langkah 3 -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                    <button type="button" @click="step = 2" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold text-xs">
                        &larr; Kembali ke Pilih Petugas
                    </button>

                    <!-- Jika ada role pengawas, lanjut ke langkah 4. Jika tidak, langsung tombol submit -->
                    <template x-if="adaPengawas">
                        <button
                            type="button"
                            @click="step = 4"
                            class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer text-xs"
                        >
                            <span>Lanjut ke Langkah 4: Alokasi Pengawas</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </template>
                    <template x-if="!adaPengawas">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer text-xs"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan &amp; Distribusikan (<span x-text="assignments.length"></span> Petugas)</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- HALAMAN 4: Alokasi Pengawas (Bisa Di-skip Jika Tidak Ada Pengawas)     -->
        <!-- ===================================================================== -->
        <div x-show="step === 4" class="space-y-6">
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-2xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Langkah 4</span>
                        <h2 class="text-xl font-bold text-slate-800 tracking-tight mt-1">Alokasi Pengawas Lapangan (PML / Supervisor)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Tentukan pengawas untuk setiap petugas yang telah dipilih di langkah sebelumnya. Anda juga dapat mengatur pengawas secara serentak.</p>
                    </div>

                    <!-- Quick Bulk Supervisor -->
                    <div class="flex flex-wrap items-center gap-2 bg-slate-50 p-2.5 rounded-2xl border border-slate-200/80 text-xs">
                        <span class="font-bold text-slate-600 text-[11px]">Set Pengawas Serentak:</span>
                        <select x-model="bulkPengawasId" class="bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs">
                            <option value="">- Pilih Pengawas -</option>
                            @foreach($pengawasCandidates as $pml)
                                <option value="{{ $pml->id }}">{{ $pml->name }} ({{ $pml->roleLabel() }})</option>
                            @endforeach
                        </select>
                        <button type="button" @click="applyBulkPengawas()" class="px-2.5 py-1 bg-slate-800 text-white font-semibold rounded-lg hover:bg-slate-900 transition cursor-pointer">Terapkan Semua</button>
                    </div>
                </div>

                <!-- Info Panduan Langkah 4 -->
                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl text-xs text-indigo-900 flex items-start gap-3">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold">Pengawasan Penugasan</p>
                        <p class="text-indigo-700/80 text-[11px] mt-0.5">
                            Pengawas bertugas memverifikasi hasil entri dokumen / data lapangan dari petugas pendata yang diawasinya. Jika petugas tidak memerlukan pengawas, opsi ini dapat dikosongkan.
                        </p>
                    </div>
                </div>

                <!-- Tabel Mapping Petugas vs Pengawas -->
                <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Petugas Terpilih</th>
                                <th class="py-3 px-4">Wilayah Tugas &amp; Target Beban</th>
                                <th class="py-3 px-4">Pengawas yang Ditugaskan (PML)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(assign, index) in assignments" :key="assign.user_id">
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-bold" x-text="index + 1"></td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full" :class="(assign.petugas?.tipe || 'organik') === 'mitra' ? 'bg-sky-500' : 'bg-emerald-500'"></span>
                                            <span x-text="assign.petugas?.name || ('Petugas #' + assign.user_id)"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            <span class="capitalize" x-text="assign.petugas?.tipe"></span> &bull; ID/NIP: <span class="font-mono text-slate-600 font-semibold" x-text="assign.petugas?.nip_sobat"></span>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-1">
                                            <p class="font-bold text-slate-700">
                                                <span x-text="assign.target_volume"></span> <span x-text="assign.satuan_volume"></span>
                                            </p>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="w in getCleanedWilayah(assign)" :key="w">
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200" x-text="w"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <select
                                            :name="'assignments[' + index + '][pengawas_id]'"
                                            x-model="assign.pengawas_id"
                                            class="w-full max-w-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                        >
                                            <option value="">- Tanpa Pengawas / Mandiri -</option>
                                            @foreach($pengawasCandidates as $pml)
                                                <option value="{{ $pml->id }}">{{ $pml->name }} ({{ $pml->roleLabel() }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Navigasi Langkah 4 -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                    <button type="button" @click="step = 3" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold text-xs">
                        &larr; Kembali ke Wilayah &amp; Beban
                    </button>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="skipPengawas()"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold text-xs"
                        >
                            Lewati Pengawas
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer text-xs"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan &amp; Selesaikan Distribusi (<span x-text="assignments.length"></span> Petugas)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function penugasanWizard() {
    return {
        step: 1,
        metode: 'manual', // 'manual' | 'excel'
        levelWilayah: '{{ $sub->level_wilayah ?: "kecamatan" }}',
        adaPengawas: {{ $sub->ada_pengawas ? 'true' : 'false' }},
        petugasList: [],
        selectedPetugasIds: [],
        selectedPetugasMap: {}, // id => pet object
        searchKeyword: '',
        filterTipe: 'all',
        currentPage: 1,
        perPage: 15,
        totalRecords: 0,
        lastPage: 1,
        isLoading: false,
        bulkVolume: 1,
        bulkPengawasId: '',
        assignments: [],

        levelOptions: [
            { id: 'kecamatan', label: 'Kecamatan', desc: 'Wilayah level kecamatan di Wonosobo' },
            { id: 'desa', label: 'Desa / Kel', desc: 'Desa atau kelurahan sasaran' },
            { id: 'sls', label: 'SLS (RT/RW/Dusun)', desc: 'Satuan Lingkungan Setempat' },
            { id: 'sub_sls', label: 'Sub-SLS', desc: 'Pecahan bagian dalam SLS' },
            { id: 'nks', label: 'NKS', desc: 'Nomor Kode Sampel Statistik' },
        ],

        defaultEstimasiJam: {{ (float) ($sub->estimasi_jam_per_satuan ?: 7) }},
        defaultHonorSatuan: {{ (float) ($sub->honor_per_satuan ?: 0) }},
        defaultDeadline: '{{ $sub->deadline_tahapan?->format('Y-m-d') ?? '' }}',
        kuotaJam: {{ (float) $infoBulan['kuota_jam'] }},
        sbml: {{ (float) $infoBulan['sbml'] }},

        init() {
            this.fetchPetugas(1);
        },

        levelWilayahLabel(id) {
            const found = this.levelOptions.find(o => o.id === id);
            return found ? found.label : id;
        },

        setLevelWilayah(lvlId) {
            this.levelWilayah = lvlId;
        },

        fetchPetugas(page = 1) {
            if (page < 1) page = 1;
            this.isLoading = true;
            const url = '{{ route('penugasan.wizard.petugas_data', $sub) }}?page=' + page 
                + '&per_page=' + this.perPage 
                + '&tipe=' + this.filterTipe 
                + '&search=' + encodeURIComponent(this.searchKeyword);

            fetch(url)
                .then(res => res.json())
                .then(res => {
                    this.petugasList = res.data || [];
                    this.currentPage = res.current_page || 1;
                    this.totalRecords = res.recordsTotal || 0;
                    this.lastPage = res.last_page || 1;
                    this.isLoading = false;

                    // Cache petugas yang termuat ke map
                    this.petugasList.forEach(p => {
                        this.selectedPetugasMap[p.id] = p;
                    });
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        isAllPageSelected() {
            if (this.petugasList.length === 0) return false;
            return this.petugasList.every(p => this.selectedPetugasIds.some(id => id == p.id));
        },

        toggleSelectAllPage() {
            if (this.isAllPageSelected()) {
                const idsToRemove = this.petugasList.map(p => p.id);
                this.selectedPetugasIds = this.selectedPetugasIds.filter(id => !idsToRemove.some(remId => remId == id));
            } else {
                const current = new Set(this.selectedPetugasIds);
                this.petugasList.forEach(p => {
                    current.add(p.id);
                    this.selectedPetugasMap[p.id] = p;
                });
                this.selectedPetugasIds = Array.from(current);
            }
        },

        togglePetugas(pet) {
            const id = pet.id;
            const idx = this.selectedPetugasIds.findIndex(item => item == id);
            if (idx > -1) {
                this.selectedPetugasIds.splice(idx, 1);
            } else {
                this.selectedPetugasIds.push(id);
                this.selectedPetugasMap[id] = pet;
            }
        },

        async proceedToStep3() {
            if (this.selectedPetugasIds.length === 0) return;

            // Jika ada petugas terpilih yang belum ada di local cache map, fetch detailnya
            const missingIds = this.selectedPetugasIds.filter(id => !this.selectedPetugasMap[id]);
            if (missingIds.length > 0) {
                try {
                    const res = await fetch('{{ route('penugasan.wizard.petugas_details', $sub) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ids: missingIds })
                    });
                    const extraPets = await res.json();
                    extraPets.forEach(p => {
                        this.selectedPetugasMap[p.id] = p;
                    });
                } catch (e) {
                    console.error('Gagal mengambil detail petugas:', e);
                }
            }

            // Bangun assignments jika belum ada
            const existingMap = new Map(this.assignments.map(a => [a.user_id, a]));
            const newAssignments = [];

            this.selectedPetugasIds.forEach(id => {
                const pet = this.selectedPetugasMap[id];
                if (existingMap.has(id)) {
                    const existing = existingMap.get(id);
                    if (!existing.petugas && pet) {
                        existing.petugas = pet;
                    }
                    if (!existing.wilayah_tugas || existing.wilayah_tugas.length === 0) {
                        existing.wilayah_tugas = [''];
                    }
                    newAssignments.push(existing);
                } else {
                    // Default awal wilayah belum diisi (string kosong '') agar card langsung berstatus BELUM LENGKAP (merah pastel)
                    newAssignments.push({
                        user_id: id,
                        petugas: pet || {
                            id: id,
                            name: 'Petugas #' + id,
                            tipe: 'mitra',
                            nip_sobat: '-',
                            kecamatan_nama: '-',
                            kecamatan_id: '',
                            workload: { total_jam: 0, total_honor: 0 }
                        },
                        pengawas_id: '',
                        kecamatan_id: pet ? pet.kecamatan_id : '',
                        wilayah_tugas: [''], // Default kosong -> kartu merah
                        target_volume: 1,
                        satuan_volume: 'Dokumen',
                        estimasi_jam_per_satuan: this.defaultEstimasiJam,
                        honor_per_satuan: this.defaultHonorSatuan,
                        deadline_tugas: this.defaultDeadline,
                        dapat_transport: false,
                        tgl_tugas_transport: '',
                        nominal_transport: 150000,
                    });
                }
            });

            this.assignments = newAssignments;
            this.step = 3;
            this.$nextTick(() => { lucide.createIcons(); });
        },

        addWilayahItem(assign) {
            if (!assign.wilayah_tugas) assign.wilayah_tugas = [];
            assign.wilayah_tugas.push('');
            this.$nextTick(() => { lucide.createIcons(); });
        },

        removeWilayahItem(assign, idx) {
            if (assign.wilayah_tugas.length > 1) {
                assign.wilayah_tugas.splice(idx, 1);
            }
        },

        getWilayahPlaceholder(assign) {
            const lvl = this.levelWilayah;
            const domisili = assign.petugas?.kecamatan_nama ? 'Kec. ' + assign.petugas.kecamatan_nama : '';
            if (lvl === 'kecamatan') return domisili ? domisili : 'Nama Kecamatan (misal: Kec. Wonosobo)';
            if (lvl === 'desa') return 'Nama Desa / Kelurahan (misal: Desa Kalianget)';
            if (lvl === 'sls') return 'Kode / Nama SLS (misal: RT 02 / RW 01)';
            if (lvl === 'sub_sls') return 'Kode Sub-SLS (misal: Sub-SLS 001)';
            if (lvl === 'nks') return 'Nomor Kode Sampel (NKS) (misal: 3307010001)';
            return 'Nama Wilayah Tugas';
        },

        getCleanedWilayah(assign) {
            if (!assign.wilayah_tugas || !Array.isArray(assign.wilayah_tugas)) return [];
            return assign.wilayah_tugas.filter(w => w && String(w).trim() !== '');
        },

        isAssignmentComplete(assign) {
            // Syarat lengkap:
            // 1. Target volume > 0
            if (!assign.target_volume || assign.target_volume <= 0) return false;

            // 2. Wilayah tugas harus terisi minimal 1 yang tidak kosong (karena defaultnya merah)
            const filledWilayah = this.getCleanedWilayah(assign);
            if (filledWilayah.length === 0) return false;

            // 3. Jika alokasi transport dicentang, tanggal transport harus diisi
            if (assign.dapat_transport && (!assign.tgl_tugas_transport || assign.tgl_tugas_transport.trim() === '')) return false;

            return true;
        },

        getIncompleteReason(assign) {
            const filledWilayah = this.getCleanedWilayah(assign);
            if (filledWilayah.length === 0) return 'Wilayah belum diisi';
            if (!assign.target_volume || assign.target_volume <= 0) return 'Target beban kosong';
            if (assign.dapat_transport && (!assign.tgl_tugas_transport || assign.tgl_tugas_transport.trim() === '')) return 'Tgl transport belum diisi';
            return 'Belum Lengkap';
        },

        completedAssignmentsCount() {
            return this.assignments.filter(a => this.isAssignmentComplete(a)).length;
        },

        completionPercentage() {
            if (this.assignments.length === 0) return 0;
            return Math.round((this.completedAssignmentsCount() / this.assignments.length) * 100);
        },

        removeAssignment(index) {
            const removed = this.assignments.splice(index, 1);
            if (removed.length > 0) {
                this.selectedPetugasIds = this.selectedPetugasIds.filter(id => id != removed[0].user_id);
            }
        },

        applyBulkVolume() {
            const vol = parseInt(this.bulkVolume) || 1;
            this.assignments.forEach(a => {
                a.target_volume = vol;
            });
        },

        applyBulkPengawas() {
            const pId = this.bulkPengawasId;
            this.assignments.forEach(a => {
                a.pengawas_id = pId;
            });
        },

        skipPengawas() {
            // Kosongkan pengawas untuk semua dan submit
            this.assignments.forEach(a => { a.pengawas_id = ''; });
            const form = document.querySelector('form');
            if (form) form.submit();
        },

        calculateLiveJam(assign) {
            const currentJam = assign.petugas.workload?.total_jam || 0;
            const addedJam = (assign.target_volume || 0) * (assign.estimasi_jam_per_satuan || 1);
            const total = Math.round((currentJam + addedJam) * 10) / 10;
            const persen = this.kuotaJam > 0 ? Math.round((total / this.kuotaJam) * 100) : 0;
            return {
                total: total,
                persen: persen,
                is_aman: total <= this.kuotaJam,
            };
        },

        calculateLiveHonor(assign) {
            const currentHonor = assign.petugas.workload?.total_honor || 0;
            const addedHonor = (assign.target_volume || 0) * (assign.honor_per_satuan || 0);
            const total = currentHonor + addedHonor;
            const persen = this.sbml > 0 ? Math.round((total / this.sbml) * 100) : 0;
            return {
                total: total,
                persen: persen,
                is_aman: total <= this.sbml,
            };
        }
    }
}
</script>
@endsection
