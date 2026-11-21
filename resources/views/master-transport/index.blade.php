@extends('layouts.app')

@section('title', 'Master Transport Lokal & Luar Kabupaten')

@section('content')
<div x-data="{
    openAdd: false,
    openImport: false,
    editItem: null,

    openEdit(item) {
        this.editItem = {
            id: item.id,
            kecamatan_asal_id: item.kecamatan_asal_id,
            kecamatan_tujuan_id: item.kecamatan_tujuan_id,
            nominal: Number(item.nominal)
        };
    }
}" class="space-y-6">

    <!-- Header & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Master Transport</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola tarif transport antar-kecamatan (terhubung ke Master Wilayah), batas maksimal harian dalam kabupaten, dan tarif perjalanan dinas luar kabupaten.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('master.transport.template') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 rounded-xl transition shadow-2xs">
                <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Template Excel</span>
            </a>
            <button @click="openImport = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-emerald-700 text-white hover:bg-emerald-800 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                <span>Import Excel</span>
            </button>
            <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Tarif Rute</span>
            </button>
        </div>
    </div>

    <!-- Pengaturan Nilai Maksimal Dalam Kabupaten & Tarif Luar Kabupaten -->
    <div class="bg-gradient-to-br from-indigo-50/50 via-white to-sky-50/40 border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs">
        <form method="POST" action="{{ route('master.transport.settings') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            @csrf
            @method('PUT')
            <div class="md:col-span-4 space-y-1">
                <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Aturan Tarif Harian</span>
                <h2 class="text-sm font-bold text-slate-800">Batas Maksimal &amp; Tarif Luar Kabupaten</h2>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Jika perjalanan &gt; 1 kecamatan dalam 1 hari, tarif tiap rute dijumlahkan dengan batas maksimal sesuai ketentuan di samping.
                </p>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maks. Dalam Kabupaten / Hari (Rp)</label>
                <input
                    type="number"
                    name="maks_transport_dalam_kab"
                    value="{{ $maksDalamKab }}"
                    min="0"
                    required
                    class="w-full text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif Luar Kabupaten / Hari (Rp)</label>
                <input
                    type="number"
                    name="transport_luar_kab"
                    value="{{ $tarifLuarKab }}"
                    min="0"
                    required
                    class="w-full text-xs font-bold text-indigo-800 bg-white border border-indigo-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">
                    Simpan Aturan
                </button>
            </div>
        </form>
    </div>

    <!-- Switcher Mode Tampilan (Daftar Rute vs Matriks 15x15) -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-2xs space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="inline-flex p-1 bg-slate-100/80 rounded-2xl border border-slate-200/60 self-start">
                <a href="{{ route('master.transport.index', ['mode' => 'list']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $viewMode === 'list' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    Daftar Tarif Rute (CRUD)
                </a>
                <a href="{{ route('master.transport.index', ['mode' => 'matrix']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $viewMode === 'matrix' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    Matriks Tabel 15&times;15
                </a>
            </div>

            @if($viewMode === 'list')
            <form method="GET" action="{{ route('master.transport.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="mode" value="list">
                <select name="asal_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                    <option value="">Semua Kec. Asal</option>
                    @foreach($kecamatans as $k)
                        <option value="{{ $k->id }}" {{ (string)$asalId === (string)$k->id ? 'selected' : '' }}>Asal: {{ $k->nama }}</option>
                    @endforeach
                </select>
                <select name="tujuan_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                    <option value="">Semua Kec. Tujuan</option>
                    @foreach($kecamatans as $k)
                        <option value="{{ $k->id }}" {{ (string)$tujuanId === (string)$k->id ? 'selected' : '' }}>Tujuan: {{ $k->nama }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-semibold hover:bg-slate-900 cursor-pointer">Filter</button>
                @if($asalId || $tujuanId)
                    <a href="{{ route('master.transport.index', ['mode' => 'list']) }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </form>
            @endif
        </div>

        @if($viewMode === 'list')
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">No</th>
                                <th class="py-3 px-4">Kecamatan Asal</th>
                                <th class="py-3 px-4">Kecamatan Tujuan</th>
                                <th class="py-3 px-4 text-right">Nominal Transport</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($transports as $idx => $tr)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-semibold">{{ $transports->firstItem() + $idx }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-800">
                                        Kec. {{ $tr->kecamatanAsal?->nama ?? '-' }}
                                        <span class="text-[10px] font-mono text-slate-400 ml-1">[ID: {{ $tr->kecamatan_asal_id }}]</span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-800">
                                        Kec. {{ $tr->kecamatanTujuan?->nama ?? '-' }}
                                        <span class="text-[10px] font-mono text-slate-400 ml-1">[ID: {{ $tr->kecamatan_tujuan_id }}]</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-emerald-700">
                                        Rp {{ number_format($tr->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                        <button type="button" @click="openEdit({{ $tr->toJson() }})" class="p-1.5 text-slate-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition cursor-pointer" title="Edit">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <form method="POST" action="{{ route('master.transport.destroy', $tr) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus tarif transport rute ini?', 'Hapus Tarif')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Hapus">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">Belum ada data master transport.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pt-2">
                {{ $transports->links() }}
            </div>
        @else
            <!-- Mode Matriks 15x15 -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
                <div class="overflow-x-auto max-h-[600px]">
                    <table class="w-full text-center border-collapse text-[11px]">
                        <thead class="sticky top-0 z-20 bg-slate-100 border-b border-slate-200 font-bold text-slate-700">
                            <tr>
                                <th class="sticky left-0 z-30 bg-slate-100 py-2.5 px-3 text-left border-r border-slate-200 min-w-[130px]">Asal \ Tujuan</th>
                                @foreach($kecamatans as $kCol)
                                    <th class="py-2.5 px-2 border-r border-slate-200/70 min-w-[85px]">{{ $kCol->nama }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($kecamatans as $kRow)
                                <tr class="hover:bg-indigo-50/30">
                                    <td class="sticky left-0 z-10 bg-slate-50 py-2 px-3 text-left font-bold text-slate-800 border-r border-slate-200">
                                        {{ $kRow->nama }}
                                    </td>
                                    @foreach($kecamatans as $kCol)
                                        @php
                                            $val = $matrixData[$kRow->id][$kCol->id] ?? null;
                                            $isSame = $kRow->id === $kCol->id;
                                        @endphp
                                        <td class="py-2 px-2 border-r border-slate-100 font-mono {{ $isSame ? 'bg-slate-100/70 font-bold text-slate-700' : 'text-slate-600' }}">
                                            {{ $val !== null ? number_format($val, 0, ',', '.') : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Tambah Tarif Rute -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="openAdd = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Tambah / Update Tarif Rute</h3>
                <button @click="openAdd = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" action="{{ route('master.transport.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Asal</label>
                    <select name="kecamatan_asal_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white">
                        @foreach($kecamatans as $k)
                            <option value="{{ $k->id }}" {{ strtolower($k->nama) === 'wonosobo' ? 'selected' : '' }}>Kec. {{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Tujuan</label>
                    <select name="kecamatan_tujuan_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white">
                        @foreach($kecamatans as $k)
                            <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nominal Transport (Rp)</label>
                    <input type="number" name="nominal" min="0" required placeholder="130000" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openAdd = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Tarif Rute -->
    <div x-show="editItem" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="editItem = null" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100" x-if="editItem">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Edit Tarif Rute Transport</h3>
                <button @click="editItem = null" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" :action="'/master/transport/' + editItem.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Asal</label>
                    <select name="kecamatan_asal_id" x-model="editItem.kecamatan_asal_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white">
                        @foreach($kecamatans as $k)
                            <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Tujuan</label>
                    <select name="kecamatan_tujuan_id" x-model="editItem.kecamatan_tujuan_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white">
                        @foreach($kecamatans as $k)
                            <option value="{{ $k->id }}">Kec. {{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nominal Transport (Rp)</label>
                    <input type="number" name="nominal" x-model.number="editItem.nominal" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editItem = null" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Excel -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="openImport = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Import Master Transport Excel</h3>
                <button @click="openImport = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" action="{{ route('master.transport.import') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1">
                    <p class="font-bold text-slate-700">Format Kolom Excel</p>
                    <p class="text-[11px] text-slate-500">id_kec_asal | kecamatan_asal | id_kec_tujuan | kecamatan_tujuan | nominal</p>
                    <a href="{{ route('master.transport.template') }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:underline pt-1">
                        <i data-lucide="download" class="w-3 h-3"></i> Download Template Resmi
                    </a>
                </div>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-white">
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openImport = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-700 text-white font-semibold hover:bg-emerald-800 cursor-pointer">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
