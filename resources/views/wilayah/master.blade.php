@extends('layouts.app')

@section('title', 'Master Data Wilayah')

@section('content')
<div x-data="masterWilayahPage()" class="space-y-6">
    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Master Data Wilayah</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola master hierarki wilayah kerja statistik Kabupaten Wonosobo: Kecamatan, Desa/Kelurahan, SLS, Sub-SLS, dan NKS.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('master.wilayah.template', $tab) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 rounded-xl transition shadow-2xs">
                <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Download Template ({{ strtoupper($tab) }})</span>
            </a>
            <button @click="openImportModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-emerald-700 text-white hover:bg-emerald-800 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                <span>Import Excel</span>
            </button>
            <button @click="openAddModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Data</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tab 5 Level Wilayah (Lega & Pastel) -->
    <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-wrap gap-1">
        <a href="{{ route('master.wilayah.index', ['tab' => 'kecamatan']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $tab === 'kecamatan' ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            1. Kecamatan
        </a>
        <a href="{{ route('master.wilayah.index', ['tab' => 'desa']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $tab === 'desa' ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            2. Desa / Kelurahan
        </a>
        <a href="{{ route('master.wilayah.index', ['tab' => 'sls']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $tab === 'sls' ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            3. SLS (RT/RW/Dusun)
        </a>
        <a href="{{ route('master.wilayah.index', ['tab' => 'sub_sls']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $tab === 'sub_sls' ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            4. Sub-SLS
        </a>
        <a href="{{ route('master.wilayah.index', ['tab' => 'nks']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $tab === 'nks' ? 'bg-slate-800 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            5. NKS (Nomor Kode Sampel)
        </a>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-4">
        <form method="GET" action="{{ route('master.wilayah.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari wilayah {{ $tab }} (nama, kode, desa, kecamatan)..."
                    class="w-full text-xs bg-slate-50/80 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"
                >
            </div>
            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-slate-800 text-white font-semibold rounded-xl text-xs hover:bg-slate-900 transition cursor-pointer">
                Cari
            </button>
            @if($search)
                <a href="{{ route('master.wilayah.index', ['tab' => $tab]) }}" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-xs hover:bg-slate-50 transition">
                    Reset
                </a>
            @endif
        </form>

        <!-- Tabel Data Sesuai Tab Aktif -->
        <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        @if($tab === 'kecamatan')
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID</th>
                                <th class="py-3 px-4 w-32">Kode BPS</th>
                                <th class="py-3 px-4">Nama Kecamatan</th>
                                <th class="py-3 px-4 w-36 text-center">Jumlah Desa</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        @elseif($tab === 'desa')
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID</th>
                                <th class="py-3 px-4">Kecamatan</th>
                                <th class="py-3 px-4">Kode Desa</th>
                                <th class="py-3 px-4 font-bold">Nama Desa / Kelurahan</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        @elseif($tab === 'sls')
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID</th>
                                <th class="py-3 px-4">Kecamatan</th>
                                <th class="py-3 px-4">Desa / Kelurahan</th>
                                <th class="py-3 px-4">ID SLS</th>
                                <th class="py-3 px-4 font-bold">Nama SLS (RT/RW/Dusun)</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        @elseif($tab === 'sub_sls')
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID</th>
                                <th class="py-3 px-4">Kecamatan</th>
                                <th class="py-3 px-4">Desa / Kelurahan</th>
                                <th class="py-3 px-4">ID SLS</th>
                                <th class="py-3 px-4 font-bold">Nama / Kode Sub-SLS</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        @elseif($tab === 'nks')
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID</th>
                                <th class="py-3 px-4">Kecamatan</th>
                                <th class="py-3 px-4">Desa / Kelurahan</th>
                                <th class="py-3 px-4 font-bold">Kode NKS</th>
                                <th class="py-3 px-4">Nama SLS</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 text-center font-mono text-slate-400 font-semibold">{{ $item->id }}</td>

                                @if($tab === 'kecamatan')
                                    <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ $item->kode }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-800 text-sm">Kec. {{ $item->nama }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                            {{ $item->desas_count }} Desa
                                        </span>
                                    </td>
                                @elseif($tab === 'desa')
                                    <td class="py-3 px-4 text-slate-600">Kec. {{ $item->kecamatan?->nama ?? '-' }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-500">{{ $item->kd_desa ?? '-' }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-800 text-sm">{{ $item->desa }}</td>
                                @elseif($tab === 'sls')
                                    <td class="py-3 px-4 text-slate-600">Kec. {{ $item->kecamatan?->nama ?? '-' }}</td>
                                    <td class="py-3 px-4 text-slate-700 font-medium">Desa {{ $item->desa?->desa ?? '-' }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-500">{{ $item->id_sls ?? '-' }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-800">{{ $item->sls }}</td>
                                @elseif($tab === 'sub_sls')
                                    <td class="py-3 px-4 text-slate-600">Kec. {{ $item->kecamatan?->nama ?? '-' }}</td>
                                    <td class="py-3 px-4 text-slate-700 font-medium">Desa {{ $item->desa?->desa ?? '-' }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-500">{{ $item->id_sls ?? '-' }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-800">{{ $item->subsls }}</td>
                                @elseif($tab === 'nks')
                                    <td class="py-3 px-4 text-slate-600">Kec. {{ $item->kecamatan?->nama ?? '-' }}</td>
                                    <td class="py-3 px-4 text-slate-700 font-medium">Desa {{ $item->desa?->desa ?? '-' }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-indigo-700 text-sm">{{ $item->nks }}</td>
                                    <td class="py-3 px-4 text-slate-700">{{ $item->sls ?? '-' }}</td>
                                @endif

                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openEditModal({{ json_encode($item) }})"
                                        class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition cursor-pointer"
                                        title="Edit data wilayah"
                                    >
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('master.wilayah.destroy', $item->id) }}"
                                        class="inline"
                                        onsubmit="return optimaConfirm(event, 'Hapus data wilayah ini? Seluruh data bawahan yang terhubung juga dapat terpengaruh.', 'Hapus Data Wilayah')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="tab" value="{{ $tab }}">
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Hapus">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 text-xs italic">
                                    Belum ada data wilayah untuk level {{ strtoupper($tab) }}. Silakan tambah data atau import dari file Excel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-2">
            {{ $data->links() }}
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT DATA WILAYAH (LEGA, RAPI & PASTEL) -->
    <div
        x-show="showFormModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6"
    >
        <div
            @click.away="showFormModal = false"
            class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800" x-text="isEdit ? 'Edit Data Wilayah' : 'Tambah Data Wilayah'"></h3>
                        <p class="text-[11px] text-slate-400">Level: <strong class="text-indigo-600 uppercase" x-text="currentTab"></strong></p>
                    </div>
                </div>
                <button @click="showFormModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="formActionUrl" method="POST" class="space-y-4 text-xs">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="tab" :value="currentTab">

                <!-- Form Fields untuk Kecamatan -->
                <template x-if="currentTab === 'kecamatan'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Kode Wilayah Kecamatan</label>
                            <input type="text" name="kode" x-model="formData.kode" required placeholder="330701" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama Kecamatan</label>
                            <input type="text" name="nama" x-model="formData.nama" required placeholder="Wadaslintang" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-bold text-slate-800">
                        </div>
                    </div>
                </template>

                <!-- Form Fields untuk Desa -->
                <template x-if="currentTab === 'desa'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Induk</label>
                            <select name="id_kec" x-model="formData.id_kec" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                                <option value="">- Pilih Kecamatan -</option>
                                @foreach($kecamatans as $k)
                                    <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Kode Desa (Opsional)</label>
                            <input type="text" name="kd_desa" x-model="formData.kd_desa" placeholder="3307010001" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama Desa / Kelurahan</label>
                            <input type="text" name="desa" x-model="formData.desa" required placeholder="Nama Desa" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-bold text-slate-800">
                        </div>
                    </div>
                </template>

                <!-- Form Fields untuk SLS -->
                <template x-if="currentTab === 'sls'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan</label>
                                <select name="id_kec" x-model="formData.id_kec" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Kec -</option>
                                    @foreach($kecamatans as $k)
                                        <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Desa / Kelurahan</label>
                                <select name="id_desa" x-model="formData.id_desa" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Desa -</option>
                                    <template x-for="d in filteredDesasModal()" :key="d.id">
                                        <option :value="d.id" x-text="d.desa"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">ID SLS (Kode SLS BPS)</label>
                            <input type="text" name="id_sls" x-model="formData.id_sls" placeholder="0001" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama SLS (RT / RW / Dusun)</label>
                            <input type="text" name="sls" x-model="formData.sls" required placeholder="RT 001 RW 003 Dusun Krajan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-bold text-slate-800 focus:outline-none">
                        </div>
                    </div>
                </template>

                <!-- Form Fields untuk Sub-SLS -->
                <template x-if="currentTab === 'sub_sls'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan</label>
                                <select name="id_kec" x-model="formData.id_kec" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Kec -</option>
                                    @foreach($kecamatans as $k)
                                        <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Desa / Kelurahan</label>
                                <select name="id_desa" x-model="formData.id_desa" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Desa -</option>
                                    <template x-for="d in filteredDesasModal()" :key="d.id">
                                        <option :value="d.id" x-text="d.desa"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">ID SLS Induk</label>
                            <input type="text" name="id_sls" x-model="formData.id_sls" placeholder="0001" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama / Kode Sub-SLS</label>
                            <input type="text" name="subsls" x-model="formData.subsls" required placeholder="Sub-SLS 01" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-bold text-slate-800 focus:outline-none">
                        </div>
                    </div>
                </template>

                <!-- Form Fields untuk NKS -->
                <template x-if="currentTab === 'nks'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan</label>
                                <select name="id_kec" x-model="formData.id_kec" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Kec -</option>
                                    @foreach($kecamatans as $k)
                                        <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1.5">Desa / Kelurahan</label>
                                <select name="id_desa" x-model="formData.id_desa" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none">
                                    <option value="">- Pilih Desa -</option>
                                    <template x-for="d in filteredDesasModal()" :key="d.id">
                                        <option :value="d.id" x-text="d.desa"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Kode NKS (Nomor Kode Sampel)</label>
                            <input type="text" name="nks" x-model="formData.nks" required placeholder="3307010001" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-mono font-bold text-indigo-700 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama SLS (Opsional)</label>
                            <input type="text" name="sls" x-model="formData.sls" placeholder="RT 001 RW 002" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none">
                        </div>
                    </div>
                </template>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="showFormModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL IMPORT EXCEL DATA WILAYAH -->
    <div
        x-show="openImportModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6"
    >
        <div
            @click.away="openImportModal = false"
            class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Import Data Wilayah</h3>
                        <p class="text-[11px] text-slate-400">Level Target: <strong class="text-emerald-700 uppercase" x-text="currentTab"></strong></p>
                    </div>
                </div>
                <button @click="openImportModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('master.wilayah.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="tab" :value="currentTab">

                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1">
                    <p class="font-bold text-slate-700">Format File Excel (.xlsx/.csv)</p>
                    <p class="text-[11px] text-slate-500">Pastikan susunan kolom sesuai dengan template resmi level ini.</p>
                    <a :href="'{{ url('/master/wilayah/template') }}/' + currentTab" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:underline pt-1">
                        <i data-lucide="download" class="w-3 h-3"></i>
                        <span>Download Template Resmi</span>
                    </a>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Pilih Berkas Excel</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-3 bg-white file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="openImportModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-700 text-white font-semibold hover:bg-emerald-800 transition shadow-2xs cursor-pointer">
                        Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function masterWilayahPage() {
    return {
        currentTab: '{{ $tab }}',
        showFormModal: false,
        openImportModal: false,
        isEdit: false,
        formActionUrl: '{{ route('master.wilayah.store') }}',
        allDesas: @json($desasList),
        formData: {
            id: null,
            kode: '',
            nama: '',
            id_kec: '',
            kd_desa: '',
            desa: '',
            id_sls: '',
            sls: '',
            subsls: '',
            nks: ''
        },

        filteredDesasModal() {
            if (!this.formData.id_kec) return this.allDesas;
            return this.allDesas.filter(d => d.id_kec == this.formData.id_kec);
        },

        openAddModal() {
            this.isEdit = false;
            this.formActionUrl = '{{ route('master.wilayah.store') }}';
            this.formData = {
                id: null,
                kode: '',
                nama: '',
                id_kec: '',
                kd_desa: '',
                desa: '',
                id_sls: '',
                sls: '',
                subsls: '',
                nks: ''
            };
            this.showFormModal = true;
            this.$nextTick(() => { lucide.createIcons(); });
        },

        openEditModal(item) {
            this.isEdit = true;
            this.formActionUrl = '{{ url('/master/wilayah') }}/' + item.id;
            this.formData = {
                id: item.id,
                kode: item.kode || '',
                nama: item.nama || '',
                id_kec: item.id_kec || '',
                kd_desa: item.kd_desa || '',
                desa: item.desa || '',
                id_sls: item.id_sls || '',
                sls: item.sls || '',
                subsls: item.subsls || '',
                nks: item.nks || ''
            };
            this.showFormModal = true;
            this.$nextTick(() => { lucide.createIcons(); });
        }
    }
}
</script>
@endsection
