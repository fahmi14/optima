@extends('layouts.app')

@section('title', 'Master Tim Kerja')

@section('content')
<div x-data="{ openAdd: false, editTim: null }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Master Tim Kerja BPS Wonosobo</h1>
            <p class="text-xs text-slate-500">Daftar tim kerja teknis dan subbagian.</p>
        </div>
        <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>Tambah Tim</span>
        </button>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                    <th class="py-3 px-4">Kode</th>
                    <th class="py-3 px-4">Nama Tim Kerja</th>
                    <th class="py-3 px-4">Ketua Tim</th>
                    <th class="py-3 px-4">Anggota / Kegiatan</th>
                    <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($timKerjas as $tim)
                <tr class="hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-bold text-slate-700">{{ $tim->kode }}</td>
                    <td class="py-3 px-4 font-semibold text-slate-800">{{ $tim->nama }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $tim->ketua_tim ?? '-' }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ $tim->users_count }} personil &middot; {{ $tim->master_kegiatans_count }} kegiatan</td>
                    <td class="py-3 px-4 text-right space-x-1">
                        <button @click="editTim = {{ $tim->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded-lg cursor-pointer">Edit</button>
                        <form method="POST" action="{{ route('tim.destroy', $tim) }}" class="inline" onsubmit="return confirm('Hapus Tim Kerja ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal Add -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openAdd = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Tambah Tim Kerja</h3>
            <form method="POST" action="{{ route('tim.store') }}" class="space-y-3 text-xs">
                @csrf
                <div><label class="block font-semibold mb-1">Kode</label><input type="text" name="kode" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Nama Tim Kerja</label><input type="text" name="nama" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Ketua Tim</label><input type="text" name="ketua_tim" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openAdd = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div x-show="editTim" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editTim = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="editTim">
            <h3 class="text-sm font-bold text-slate-800">Edit Tim Kerja</h3>
            <form method="POST" :action="'/master/tim/' + editTim.id" class="space-y-3 text-xs">
                @csrf @method('PUT')
                <div><label class="block font-semibold mb-1">Kode</label><input type="text" name="kode" x-model="editTim.kode" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Nama Tim Kerja</label><input type="text" name="nama" x-model="editTim.nama" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Ketua Tim</label><input type="text" name="ketua_tim" x-model="editTim.ketua_tim" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editTim = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
